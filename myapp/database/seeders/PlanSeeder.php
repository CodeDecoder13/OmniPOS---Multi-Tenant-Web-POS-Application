<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0,
                'features' => [
                    'Single branch',
                    'Up to 3 users',
                    'Up to 100 products',
                    'Basic POS',
                    'Daily reports',
                ],
                'max_branches' => 1,
                'max_users' => 3,
                'max_products' => 100,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 1499,
                'features' => [
                    'Up to 5 branches',
                    'Up to 25 users',
                    'Up to 5,000 products',
                    'Advanced POS',
                    'Advanced reports & analytics',
                    'Inventory management',
                    'Customer management',
                    'Receipt customization',
                ],
                'max_branches' => 5,
                'max_users' => 25,
                'max_products' => 5000,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price' => 3999,
                'features' => [
                    'Unlimited branches',
                    'Unlimited users',
                    'Unlimited products',
                    'Full POS suite',
                    'Real-time analytics',
                    'Advanced inventory',
                    'Customer management',
                    'Custom receipts',
                    'Priority support',
                    'API access',
                ],
                'max_branches' => null,
                'max_users' => null,
                'max_products' => null,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
