<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\AdminRequest;
use App\Models\Admin;
use App\Services\Central\AdminActivityLogService;
use App\Services\Central\AdminService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function __construct(
        private AdminService $adminService,
        private AdminActivityLogService $activityLog,
    ) {}

    public function index(Request $request): Response
    {
        $admins = $this->adminService->list($request->only(['search']));

        return Inertia::render('admin/admins/Index', [
            'admins' => $admins,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/admins/Create');
    }

    public function store(AdminRequest $request): RedirectResponse
    {
        $admin = $this->adminService->create($request->validated());

        $this->activityLog->log(
            $request->user('admin'),
            'created_admin',
            $admin,
            ['name' => $admin->name],
            $request->ip(),
        );

        return redirect()->route('admin.admins.index')->with('success', "Admin {$admin->name} created successfully.");
    }

    public function edit(int $id): Response
    {
        $admin = Admin::findOrFail($id);

        return Inertia::render('admin/admins/Edit', [
            'admin' => $admin,
        ]);
    }

    public function update(AdminRequest $request, int $id): RedirectResponse
    {
        $admin = Admin::findOrFail($id);
        $admin = $this->adminService->update($admin, $request->validated());

        $this->activityLog->log(
            $request->user('admin'),
            'updated_admin',
            $admin,
            ['name' => $admin->name],
            $request->ip(),
        );

        return redirect()->route('admin.admins.index')->with('success', "Admin {$admin->name} updated successfully.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $admin = Admin::findOrFail($id);
        $name = $admin->name;

        try {
            $this->adminService->delete($admin, $request->user('admin'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->activityLog->log(
            $request->user('admin'),
            'deleted_admin',
            null,
            ['name' => $name],
            $request->ip(),
        );

        return redirect()->route('admin.admins.index')->with('success', "Admin {$name} deleted successfully.");
    }
}
