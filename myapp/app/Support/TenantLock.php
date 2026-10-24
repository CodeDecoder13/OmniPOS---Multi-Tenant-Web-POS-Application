<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Serializes work per tenant for the rest of the current transaction.
 *
 * Used by the order/refund/PO/transfer number generators: "read the last number, add one"
 * is only safe if two requests cannot read at the same time, and there is no row to
 * lock for the first number of the day. Call it inside DB::transaction(). It is a no-op
 * on databases without advisory locks (the production database is Postgres).
 */
final class TenantLock
{
    public static function acquire(string $tenantId, string $scope = 'numbers'): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::select('select pg_advisory_xact_lock(hashtext(?))', ["{$scope}:{$tenantId}"]);
        }
    }
}
