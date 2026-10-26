<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One shared guess budget for every PIN route (verify, set, set-for-user, setup).
 *
 * A PIN is 4-6 digits, so the real protection is limiting wrong guesses, not hashing. Counting every wrong
 * guess (a failed verify OR a "choose a different PIN" collision) against one key stops an attacker from
 * multiplying their attempts by alternating routes. Budget: 10 wrong guesses per 15 minutes per signed-in user.
 */
final class PinGuard
{
    private const MAX_FAILURES = 10;

    private const WINDOW_SECONDS = 900;

    private static function key(Request $request, Tenant $tenant): string
    {
        return "pin-guess:{$tenant->id}:{$request->user()->id}";
    }

    /** Stop early (HTTP 429) when the budget is spent. */
    public static function assertNotLocked(Request $request, Tenant $tenant): void
    {
        $key = self::key($request, $tenant);

        abort_if(
            RateLimiter::tooManyAttempts($key, self::MAX_FAILURES),
            429,
            'Too many wrong PIN attempts. Please wait '.ceil(RateLimiter::availableIn($key) / 60).' minute(s) and try again.',
        );
    }

    /**
     * Is this PIN already used by someone else in the business? (It must stay unique: the PIN is what
     * identifies the cashier.) A "taken" answer costs one guess from the budget, so it cannot be used
     * to map out other people's PINs quickly.
     */
    public static function isTakenByOther(Request $request, Tenant $tenant, int $exceptUserId, string $pin): bool
    {
        $others = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', '!=', $exceptUserId)
            ->whereNotNull('pos_pin')
            ->get();

        foreach ($others as $other) {
            if (Hash::check($pin, $other->pos_pin)) {
                self::fail($request, $tenant);

                return true;
            }
        }

        return false;
    }

    /** Count one wrong guess. */
    public static function fail(Request $request, Tenant $tenant): void
    {
        RateLimiter::hit(self::key($request, $tenant), self::WINDOW_SECONDS);
    }
}
