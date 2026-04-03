<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Product;
use App\Models\TenantUser;
use DomainException;

class PlanLimitService
{
    private static array $resourceMap = [
        'branches' => [
            'field' => 'max_branches',
            'label' => 'branches',
        ],
        'users' => [
            'field' => 'max_users',
            'label' => 'users',
        ],
        'products' => [
            'field' => 'max_products',
            'label' => 'products',
        ],
    ];

    public static function ensureWithinLimit(Tenant $tenant, string $resource, int $additionalCount = 1): void
    {
        $plan = $tenant->subscription?->plan;

        if (! $plan) {
            return;
        }

        $config = self::$resourceMap[$resource] ?? null;

        if (! $config) {
            return;
        }

        $maxLimit = $plan->{$config['field']};

        // null = unlimited
        if ($maxLimit === null) {
            return;
        }

        $currentCount = self::currentCount($tenant, $resource);

        if (($currentCount + $additionalCount) > $maxLimit) {
            throw new DomainException(
                "Your {$plan->name} plan allows max {$maxLimit} {$config['label']}. You currently have {$currentCount}. Upgrade your plan to add more."
            );
        }
    }

    public static function currentCount(Tenant $tenant, string $resource): int
    {
        return match ($resource) {
            'branches' => Branch::where('tenant_id', $tenant->id)->count(),
            'users' => TenantUser::where('tenant_id', $tenant->id)->count(),
            'products' => Product::where('tenant_id', $tenant->id)->count(),
            default => 0,
        };
    }
}
