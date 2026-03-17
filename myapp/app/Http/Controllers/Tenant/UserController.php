<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        $users = $tenant->users()
            ->select('users.id', 'users.name', 'users.email', 'users.created_at')
            ->orderByPivot('created_at', 'asc')
            ->paginate(10);

        $tenantUserRoles = TenantUser::where('tenant_id', $tenant->id)
            ->with('role:id,name,slug,is_system')
            ->get()
            ->keyBy('user_id');

        $users->getCollection()->transform(function ($user) use ($tenantUserRoles) {
            $tu = $tenantUserRoles->get($user->id);
            $user->tenant_role = $tu?->role;
            $user->has_pin = $tu && $tu->pos_pin !== null;
            return $user;
        });

        $roles = Role::forTenant($tenant->id)
            ->select('id', 'name', 'slug', 'is_system')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return Inertia::render('tenant/users/Index', [
            'users' => $users,
            'ownerId' => $tenant->owner_id,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        if ((int) $request->user()->id !== (int) $tenant->owner_id) {
            return back()->with('error', 'Only the organization owner can add users.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $role = Role::forTenant($tenant->id)->findOrFail($validated['role_id']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role_id' => $role->id,
        ]);

        return back()->with('success', "User {$user->name} created successfully.");
    }

    public function update(Request $request, string $tenantSlug, int $userId): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $isOwnerRow = (int) $userId === (int) $tenant->owner_id;

        // Only the owner can edit themselves
        if ($isOwnerRow && (int) $request->user()->id !== (int) $tenant->owner_id) {
            return back()->with('error', 'Cannot edit the organization owner.');
        }

        if ($isOwnerRow) {
            // Owner editing self: only name and password
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'min:8', 'max:255'],
            ]);

            $user = User::findOrFail($userId);
            $data = ['name' => $validated['name']];
            if (!empty($validated['password'])) {
                $data['password'] = $validated['password'];
            }
            $user->update($data);
        } else {
            // Editing other users: name, email, password, role
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
                'password' => ['nullable', 'string', 'min:8', 'max:255'],
                'role_id' => ['required', 'integer', 'exists:roles,id'],
            ]);

            $role = Role::forTenant($tenant->id)->findOrFail($validated['role_id']);

            $user = User::findOrFail($userId);
            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];
            if (!empty($validated['password'])) {
                $data['password'] = $validated['password'];
            }
            $user->update($data);

            TenantUser::where('tenant_id', $tenant->id)
                ->where('user_id', $userId)
                ->update(['role_id' => $role->id]);
        }

        return back()->with('success', 'User updated successfully.');
    }

    public function remove(Request $request, string $tenantSlug, int $user): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        if ($user === $tenant->owner_id) {
            return back()->with('error', 'Cannot remove the organization owner.');
        }

        if ($user === $request->user()->id) {
            return back()->with('error', 'You cannot remove yourself.');
        }

        $tenant->users()->detach($user);

        return back()->with('success', 'User has been removed from the organization.');
    }
}
