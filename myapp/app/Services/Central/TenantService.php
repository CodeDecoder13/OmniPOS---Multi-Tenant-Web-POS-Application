<?php

namespace App\Services\Central;

use App\Enums\BusinessType;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\Tenant\CategoryService;
use App\Services\Tenant\RoleService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantService
{
    public function __construct(
        private RoleService $roleService,
        private CategoryService $categoryService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Tenant::with(['owner', 'subscription.plan']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['business_type'])) {
            $query->where('business_type', $filters['business_type']);
        }

        if (! empty($filters['plan_id'])) {
            $query->whereHas('subscription', fn ($q) => $q->where('plan_id', $filters['plan_id']));
        }

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir = $filters['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDir);

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $slug = $this->generateUniqueSlug($data['name']);
            $owner = User::findOrFail($data['owner_id']);
            $plan = Plan::findOrFail($data['plan_id']);

            $tenant = Tenant::create([
                'id' => (string) Str::uuid(),
                'name' => $data['name'],
                'slug' => $slug,
                'business_type' => $data['business_type'],
                'owner_id' => $owner->id,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $ownerRole = $this->roleService->createDefaultRoles($tenant);

            TenantUser::create([
                'user_id' => $owner->id,
                'tenant_id' => $tenant->id,
                'role_id' => $ownerRole->id,
            ]);

            TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
            ]);

            $businessType = BusinessType::from($data['business_type']);
            $this->categoryService->seedDefaults($tenant, $businessType);

            return $tenant->load(['owner', 'subscription.plan']);
        });
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update([
            'name' => $data['name'] ?? $tenant->name,
            'business_type' => $data['business_type'] ?? $tenant->business_type,
            'is_active' => $data['is_active'] ?? $tenant->is_active,
        ]);

        if (isset($data['plan_id']) && $tenant->subscription) {
            $tenant->subscription->update(['plan_id' => $data['plan_id']]);
        }

        return $tenant->fresh(['owner', 'subscription.plan']);
    }

    public function delete(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $tenant->subscription()?->delete();
            TenantUser::where('tenant_id', $tenant->id)->delete();
            $tenant->delete();
        });
    }

    public function toggle(Tenant $tenant): Tenant
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);

        return $tenant;
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
