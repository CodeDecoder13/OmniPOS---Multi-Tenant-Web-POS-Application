<?php

namespace App\Services\Central;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::withCount('tenants');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['verified'])) {
            if ($filters['verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'email_verified_at' => ! empty($data['mark_verified']) ? now() : null,
        ]);
    }

    public function update(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        $user->update($updateData);

        return $user->fresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function resetPassword(User $user): string
    {
        $newPassword = Str::random(12);
        $user->update(['password' => Hash::make($newPassword)]);

        return $newPassword;
    }

    public function toggleVerified(User $user): User
    {
        $user->update([
            'email_verified_at' => $user->email_verified_at ? null : now(),
        ]);

        return $user->fresh();
    }
}
