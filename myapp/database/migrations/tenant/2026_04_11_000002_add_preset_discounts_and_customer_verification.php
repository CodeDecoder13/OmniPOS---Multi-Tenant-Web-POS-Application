<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_preset flag to promotions
        Schema::table('promotions', function (Blueprint $table) {
            $table->boolean('is_preset')->default(false)->after('description');
        });

        // Add customer verification fields to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_customer_name')->nullable()->after('promotion_discount');
            $table->string('discount_customer_id_number')->nullable()->after('discount_customer_name');
            $table->date('discount_customer_birthday')->nullable()->after('discount_customer_id_number');
        });

        // Seed preset promotions for all existing tenants
        $tenants = DB::table('tenants')->pluck('id');

        foreach ($tenants as $tenantId) {
            $presets = [
                [
                    'tenant_id' => $tenantId,
                    'code' => 'STUDENT20',
                    'name' => 'Student Discount',
                    'type' => 'student',
                    'value' => 20.00,
                    'is_active' => true,
                    'is_preset' => true,
                    'description' => 'Student discount - 20% off with valid student ID',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tenant_id' => $tenantId,
                    'code' => 'PWD20',
                    'name' => 'PWD Discount',
                    'type' => 'pwd',
                    'value' => 20.00,
                    'is_active' => true,
                    'is_preset' => true,
                    'description' => 'Person with Disability discount - 20% off with valid PWD ID',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tenant_id' => $tenantId,
                    'code' => 'SENIOR20',
                    'name' => 'Senior Citizen Discount',
                    'type' => 'senior_citizen',
                    'value' => 20.00,
                    'is_active' => true,
                    'is_preset' => true,
                    'description' => 'Senior Citizen discount - 20% off with valid Senior Citizen ID',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            foreach ($presets as $preset) {
                // Only insert if not already exists
                $exists = DB::table('promotions')
                    ->where('tenant_id', $tenantId)
                    ->where('code', $preset['code'])
                    ->exists();

                if (! $exists) {
                    DB::table('promotions')->insert($preset);
                }
            }
        }
    }

    public function down(): void
    {
        // Remove seeded preset promotions
        DB::table('promotions')->where('is_preset', true)->delete();

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_customer_name', 'discount_customer_id_number', 'discount_customer_birthday']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('is_preset');
        });
    }
};
