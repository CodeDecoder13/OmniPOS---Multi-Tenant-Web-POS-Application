<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant\Branch;
use App\Models\TenantUser;
use App\Models\User;
use App\Http\Requests\Tenant\UserImportRequest;
use App\Services\Tenant\ActivityLogService;
use App\Services\Tenant\UserImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
        private UserImportService $userImport,
    ) {}
    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        $users = $tenant->users()
            ->select('users.id', 'users.name', 'users.email', 'users.created_at')
            ->orderByPivot('created_at', 'asc')
            ->paginate(10);

        $tenantUserRoles = TenantUser::where('tenant_id', $tenant->id)
            ->with(['role:id,name,slug,is_system', 'branch:id,name'])
            ->get()
            ->keyBy('user_id');

        $users->getCollection()->transform(function ($user) use ($tenantUserRoles) {
            $tu = $tenantUserRoles->get($user->id);
            $user->tenant_role = $tu?->role;
            $user->has_pin = $tu && $tu->pos_pin !== null;
            $user->branch = $tu?->branch;
            $user->branch_id = $tu?->branch_id;
            $user->is_active = $tu?->is_active ?? true;
            $user->last_login_at = $tu?->last_login_at;
            return $user;
        });

        $roles = Role::forTenant($tenant->id)
            ->select('id', 'name', 'slug', 'is_system')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        $branches = Branch::forTenant($tenant)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $activityLogs = $this->activityLog->list($tenant);

        return Inertia::render('tenant/users/Index', [
            'users' => $users,
            'ownerId' => $tenant->owner_id,
            'roles' => $roles,
            'branches' => $branches,
            'activityLogs' => $activityLogs,
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
            'password' => ['required', 'string', Password::default(), 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $role = Role::forTenant($tenant->id)->findOrFail($validated['role_id']);

        if (!empty($validated['branch_id'])) {
            Branch::forTenant($tenant)->findOrFail($validated['branch_id']);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role_id' => $role->id,
            'branch_id' => $validated['branch_id'] ?? null,
        ]);

        $this->activityLog->log($tenant, $request->user()->id, 'user.created', 'User', $user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role->name,
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
            // Owner editing self: name, password, and branch
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['nullable', 'string', Password::default(), 'max:255'],
                'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            ]);

            if (!empty($validated['branch_id'])) {
                Branch::forTenant($tenant)->findOrFail($validated['branch_id']);
            }

            $user = User::findOrFail($userId);
            $data = ['name' => $validated['name']];
            if (!empty($validated['password'])) {
                $data['password'] = $validated['password'];
            }
            $user->update($data);

            TenantUser::where('tenant_id', $tenant->id)
                ->where('user_id', $userId)
                ->update(['branch_id' => $validated['branch_id'] ?? null]);
        } else {
            // Editing other users: name, email, password, role, branch
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
                'password' => ['nullable', 'string', Password::default(), 'max:255'],
                'role_id' => ['required', 'integer', 'exists:roles,id'],
                'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            ]);

            $role = Role::forTenant($tenant->id)->findOrFail($validated['role_id']);

            if (!empty($validated['branch_id'])) {
                Branch::forTenant($tenant)->findOrFail($validated['branch_id']);
            }

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
                ->update([
                    'role_id' => $role->id,
                    'branch_id' => $validated['branch_id'] ?? null,
                ]);
        }

        $this->activityLog->log($tenant, $request->user()->id, 'user.updated', 'User', $userId, [
            'name' => $user->name,
        ]);

        return back()->with('success', 'User updated successfully.');
    }

    public function toggleActive(Request $request, string $tenantSlug, int $userId): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        if ((int) $request->user()->id !== (int) $tenant->owner_id) {
            return response()->json(['message' => 'Only the organization owner can toggle user status.'], 403);
        }

        if ((int) $userId === (int) $tenant->owner_id) {
            return response()->json(['message' => 'Cannot deactivate the organization owner.'], 422);
        }

        $tenantUser = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $tenantUser->update(['is_active' => ! $tenantUser->is_active]);

        $status = $tenantUser->is_active ? 'activated' : 'deactivated';

        $user = User::find($userId);
        $this->activityLog->log($tenant, $request->user()->id, "user.{$status}", 'User', $userId, [
            'name' => $user?->name,
        ]);

        return response()->json(['message' => "User has been {$status}.", 'is_active' => $tenantUser->is_active]);
    }

    public function validateImport(UserImportRequest $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $csvContent = file_get_contents($request->file('csv_file')->getRealPath());
        $result = $this->userImport->validateCsv($csvContent, $tenant);

        return response()->json($result);
    }

    public function import(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        if ((int) $request->user()->id !== (int) $tenant->owner_id) {
            return response()->json(['message' => 'Only the organization owner can import users.'], 403);
        }

        $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.name' => ['required', 'string'],
            'rows.*.email' => ['required', 'email'],
            'rows.*.role_id' => ['required', 'integer'],
            'rows.*.role_name' => ['required', 'string'],
            'rows.*.branch_id' => ['nullable', 'integer'],
        ]);

        $credentials = $this->userImport->import($tenant, $request->input('rows'));

        $this->activityLog->log($tenant, $request->user()->id, 'users.imported', null, null, [
            'count' => count($credentials),
        ]);

        return response()->json(['credentials' => $credentials]);
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

        $removedUser = User::find($user);

        $tenant->users()->detach($user);

        $this->activityLog->log($tenant, $request->user()->id, 'user.removed', 'User', $user, [
            'name' => $removedUser?->name,
        ]);

        return back()->with('success', 'User has been removed from the organization.');
    }
}
