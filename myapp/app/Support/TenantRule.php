<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rule;

/**
 * The one way to say "this id must belong to the current business" in a validation rule.
 *
 * A bare `exists:products,id` accepts any tenant's row, which is how cross-tenant references slipped in.
 * Use TenantRule::exists('products', $tenant) instead.
 */
final class TenantRule
{
    public static function exists(string $table, Tenant $tenant, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('tenant_id', $tenant->id);
    }
}
