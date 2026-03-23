<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UserRequest;
use App\Models\User;
use App\Services\Central\AdminActivityLogService;
use App\Services\Central\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private AdminActivityLogService $activityLog,
    ) {}

    public function index(Request $request): Response
    {
        $users = $this->userService->list($request->only(['search', 'verified']));

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'verified']),
        ]);
    }

    public function show(int $id): Response
    {
        $user = User::with('tenants')->findOrFail($id);

        return Inertia::render('admin/users/Show', [
            'user' => $user,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/Create');
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = $this->userService->create($request->validated());

        $this->activityLog->log(
            $request->user('admin'),
            'created_user',
            $user,
            ['name' => $user->name, 'email' => $user->email],
            $request->ip(),
        );

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} created successfully.");
    }

    public function edit(int $id): Response
    {
        $user = User::findOrFail($id);

        return Inertia::render('admin/users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(UserRequest $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user = $this->userService->update($user, $request->validated());

        $this->activityLog->log(
            $request->user('admin'),
            'updated_user',
            $user,
            ['name' => $user->name],
            $request->ip(),
        );

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} updated successfully.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $name = $user->name;

        $this->userService->delete($user);

        $this->activityLog->log(
            $request->user('admin'),
            'deleted_user',
            null,
            ['name' => $name],
            $request->ip(),
        );

        return redirect()->route('admin.users.index')->with('success', "User {$name} deleted successfully.");
    }

    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $newPassword = $this->userService->resetPassword($user);

        $this->activityLog->log(
            $request->user('admin'),
            'reset_user_password',
            $user,
            ['name' => $user->name],
            $request->ip(),
        );

        return back()->with('success', "Password reset. New password: {$newPassword}");
    }

    public function toggleVerified(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user = $this->userService->toggleVerified($user);

        $this->activityLog->log(
            $request->user('admin'),
            $user->email_verified_at ? 'verified_user' : 'unverified_user',
            $user,
            ['name' => $user->name],
            $request->ip(),
        );

        return back()->with('success', "User {$user->name} " . ($user->email_verified_at ? 'verified' : 'unverified') . '.');
    }
}
