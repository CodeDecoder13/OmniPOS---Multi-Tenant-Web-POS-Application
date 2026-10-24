<?php

namespace App\Services\Central;

use App\Enums\BusinessType;
use App\Models\Plan;
use App\Models\PromoCode;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\Tenant\CategoryService;
use App\Services\Tenant\PromotionService;
use App\Services\Tenant\RoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterService
{
    public function __construct(
        private RoleService $roleService,
        private CategoryService $categoryService,
        private PromotionService $promotionService,
    ) {}

    public function createTenantForUser(User $user, string $storeName, string $planSlug, BusinessType $businessType, ?string $promoCode = null): Tenant
    {
        return DB::transaction(function () use ($user, $storeName, $planSlug, $businessType, $promoCode) {
            $slug = $this->generateUniqueSlug($storeName);
            $plan = Plan::where('slug', $planSlug)->firstOrFail();

            $tenant = Tenant::create([
                'id' => (string) Str::uuid(),
                'name' => $storeName,
                'slug' => $slug,
                'business_type' => $businessType,
                'owner_id' => $user->id,
            ]);

            $ownerRole = $this->roleService->createDefaultRoles($tenant);

            TenantUser::create([
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'role_id' => $ownerRole->id,
            ]);

            $promoCodeId = null;
            if ($promoCode) {
                // Lock the code so two sign-ups cannot both take the last use.
                $promo = PromoCode::where('code', strtoupper($promoCode))->lockForUpdate()->first();
                if ($promo && $promo->isValidForPlan($planSlug)) {
                    $promoCodeId = $promo->id;
                    $promo->incrementUsage();
                }
            }

            // A paid plan is only granted through a valid code. If the code ran out in the meantime,
            // fail (and roll everything back) instead of quietly creating a free paid tenant.
            if (! $plan->isFree() && ! $promoCodeId) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'promo_code' => 'Invalid or expired promo code.',
                ]);
            }

            TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'promo_code_id' => $promoCodeId,
                'status' => 'active',
            ]);

            $this->categoryService->seedDefaults($tenant, $businessType);
            $this->promotionService->seedPresetDiscounts($tenant);

            return $tenant;
        });
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
