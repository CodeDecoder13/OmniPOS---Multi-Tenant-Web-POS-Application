<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_SEED_PASSWORD');

        // The platform super admin must never ship with a known password.
        if (app()->environment('production') && (! $password || strlen($password) < 12 || $password === 'password')) {
            throw new \RuntimeException('Set ADMIN_SEED_PASSWORD (at least 12 characters) before seeding the super admin in production.');
        }

        Admin::updateOrCreate(
            ['email' => 'admin@omnipos.com'],
            [
                'name' => 'Super Admin',
                'password' => $password ?: 'password', // development convenience only
            ],
        );
    }
}
