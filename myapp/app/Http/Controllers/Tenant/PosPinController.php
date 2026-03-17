<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PosPinController extends Controller
{
    public function verify(Request $request, string $tenantSlug): JsonResponse
    {
        $request->validate([
            'pin' => ['required', 'string', 'digits_between:4,6'],
        ]);

        $tenant = $request->attributes->get('current_tenant');
        $pin = $request->input('pin');

        $tenantUsers = TenantUser::where('tenant_id', $tenant->id)
            ->whereNotNull('pos_pin')
            ->with('role.permissions')
            ->get();

        foreach ($tenantUsers as $tenantUser) {
            if (Hash::check($pin, $tenantUser->pos_pin)) {
                $user = User::find($tenantUser->user_id);
                if (! $user) {
                    continue;
                }

                $role = $tenantUser->role;

                if ($role && $role->is_system && $role->slug === 'owner') {
                    $permissions = Permission::pluck('slug')->toArray();
                } else {
                    $permissions = $role ? $role->permissions->pluck('slug')->toArray() : [];
                }

                return response()->json([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'role' => $role ? [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                    ] : null,
                    'permissions' => $permissions,
                ]);
            }
        }

        return response()->json([
            'message' => 'Invalid PIN. Please try again.',
        ], 422);
    }

    public function set(Request $request, string $tenantSlug): JsonResponse
    {
        $request->validate([
            'pin' => ['required', 'string', 'digits_between:4,6', 'confirmed'],
        ]);

        $tenant = $request->attributes->get('current_tenant');
        $pin = $request->input('pin');

        // Check uniqueness within tenant
        $otherUsers = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', '!=', $request->user()->id)
            ->whereNotNull('pos_pin')
            ->get();

        foreach ($otherUsers as $other) {
            if (Hash::check($pin, $other->pos_pin)) {
                return response()->json([
                    'message' => 'This PIN is already in use by another user.',
                    'errors' => ['pin' => ['This PIN is already in use by another user.']],
                ], 422);
            }
        }

        TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $request->user()->id)
            ->update(['pos_pin' => Hash::make($pin)]);

        return response()->json(['message' => 'PIN set successfully.']);
    }

    public function setForUser(Request $request, string $tenantSlug, int $user): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        if ((int) $request->user()->id !== (int) $tenant->owner_id) {
            return back()->with('error', 'Only the organization owner can set PINs for other users.');
        }

        $request->validate([
            'pin' => ['required', 'string', 'digits_between:4,6'],
        ]);

        $pin = $request->input('pin');

        $tenantUser = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $user)
            ->firstOrFail();

        // Check uniqueness within tenant
        $otherUsers = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', '!=', $user)
            ->whereNotNull('pos_pin')
            ->get();

        foreach ($otherUsers as $other) {
            if (Hash::check($pin, $other->pos_pin)) {
                return back()->with('error', 'This PIN is already in use by another user.');
            }
        }

        $tenantUser->update(['pos_pin' => Hash::make($pin)]);

        return back()->with('success', 'POS PIN set successfully.');
    }
}
