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
use App\Services\Tenant\RoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterService
{
    public function __construct(
        private RoleService $roleService,
        private CategoryService $categoryService,
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
                $promo = PromoCode::where('code', strtoupper($promoCode))->first();
                if ($promo && $promo->isValidForPlan($planSlug)) {
                    $promoCodeId = $promo->id;
                    $promo->incrementUsage();
                }
            }

            TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'promo_code_id' => $promoCodeId,
                'status' => 'active',
            ]);

            $this->categoryService->seedDefaults($tenant, $businessType);

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
