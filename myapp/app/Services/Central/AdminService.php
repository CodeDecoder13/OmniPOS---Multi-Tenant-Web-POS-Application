<?php

namespace App\Services\Central;

use App\Models\Admin;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Admin::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Admin
    {
        return Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Admin $admin, array $data): Admin
    {
        $updateData = [
            'name' => $data['name'] ?? $admin->name,
            'email' => $data['email'] ?? $admin->email,
            'is_active' => $data['is_active'] ?? $admin->is_active,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        $admin->update($updateData);

        return $admin->fresh();
    }

    public function delete(Admin $admin, Admin $currentAdmin): void
    {
        if ($admin->id === $currentAdmin->id) {
            throw new DomainException('You cannot delete your own account.');
        }

        if (Admin::count() <= 1) {
            throw new DomainException('Cannot delete the last admin account.');
        }

        $admin->delete();
    }
}
