<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\BusinessType;
use App\Models\Plan;
use App\Models\PromoCode;
use App\Models\User;
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
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'store_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', new Enum(BusinessType::class)],
            'plan' => ['required', 'string', 'exists:plans,slug'],
            'promo_code' => ['nullable', 'string'],
        ])->validate();

        $plan = Plan::where('slug', $input['plan'])->firstOrFail();
        $isPaid = ! $plan->isFree();

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
            'password' => $input['password'],
        ]);

        $this->registerService->createTenantForUser(
            $user,
            $input['store_name'],
            $input['plan'],
            BusinessType::from($input['business_type']),
            $input['promo_code'] ?? null,
        );

        return $user;
    }
}
