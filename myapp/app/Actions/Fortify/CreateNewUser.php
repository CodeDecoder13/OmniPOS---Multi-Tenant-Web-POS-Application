<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\BusinessType;
use App\Models\Plan;
use App\Models\PromoCode;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Services\Central\RegisterService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        private RegisterService $registerService,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $googleUser = session('google_user');
        $isGoogleRegistration = ! empty($googleUser);

        $rules = [
            ...$this->profileRules(),
            'store_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', new Enum(BusinessType::class)],
            'plan' => ['required', 'string', 'exists:plans,slug'],
            'promo_code' => ['nullable', 'string'],
        ];

        if (! $isGoogleRegistration) {
            $rules['password'] = $this->passwordRules();
        }

        Validator::make($input, $rules)->validate();

        $plan = Plan::where('slug', $input['plan'])->firstOrFail();
        $isPaid = ! $plan->isFree();

        // Free plan is currently unavailable
        if (! $isPaid) {
            throw ValidationException::withMessages([
                'plan' => 'The Free plan is currently unavailable. Please select a paid plan.',
            ]);
        }

        if ($isPaid && empty($input['promo_code'])) {
            throw ValidationException::withMessages([
                'promo_code' => 'A valid promo code is required for this plan.',
            ]);
        }

        if (! empty($input['promo_code'])) {
            $promo = PromoCode::where('code', strtoupper($input['promo_code']))->first();
            if (! $promo || ! $promo->isValidForPlan($input['plan'])) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Invalid or expired promo code.',
                ]);
            }
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $isGoogleRegistration ? null : $input['password'],
        ]);

        if ($isGoogleRegistration) {
            $user->forceFill([
                'google_id' => $googleUser['id'],
                'email_verified_at' => now(),
            ])->save();

            session()->forget('google_user');
        }

        $this->registerService->createTenantForUser(
            $user,
            $input['store_name'],
            $input['plan'],
            BusinessType::from($input['business_type']),
            $input['promo_code'] ?? null,
        );

        $user->notify(new WelcomeNotification($input['store_name']));

        return $user;
    }
}
