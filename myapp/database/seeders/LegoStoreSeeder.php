<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\VariationGroup;
use App\Models\Tenant\VariationOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LegoStoreSeeder extends Seeder
{
    /**
     * Seed LEGO store products from the lego_store_products spreadsheet.
     *
     * Usage:
     *   php artisan db:seed --class=LegoStoreSeeder
     *   (will prompt for tenant selection interactively)
     *
     * Or call from another seeder:
     *   $this->call(LegoStoreSeeder::class, false, ['tenantId' => 'your-tenant-uuid']);
     */
    public function run(string $tenantId = ''): void
    {
        if (empty($tenantId)) {
            $tenants = Tenant::select('id', 'name')->get();

            if ($tenants->isEmpty()) {
                $this->command?->error('No tenants found. Create a tenant first.');
                return;
            }

            $choices = $tenants->mapWithKeys(fn ($t) => [$t->id => "{$t->name} ({$t->id})"])->toArray();
            $selected = $this->command?->choice('Select a tenant', array_values($choices));
            $tenantId = array_search($selected, $choices) ?: '';
        }

        if (empty($tenantId) || !Tenant::where('id', $tenantId)->exists()) {
            $this->command?->error('Invalid tenant ID.');
            return;
        }

        $categories = $this->seedCategories($tenantId);
        $this->seedProducts($tenantId, $categories);

        $this->command?->info("Seeded 10 LEGO products with variations for tenant [{$tenantId}].");
    }

    private function seedCategories(string $tenantId): array
    {
        $categoryNames = [
            'City',
            'Star Wars',
            'Technic',
            'NINJAGO',
            'Friends',
            'Creator',
            'Harry Potter',
            'Architecture',
            'DUPLO',
            'Marvel Super Heroes',
        ];

        $map = [];

        foreach ($categoryNames as $index => $name) {
            $category = Category::updateOrCreate(
                ['tenant_id' => $tenantId, 'slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );

            $map[$name] = $category->id;
        }

        return $map;
    }

    private function seedProducts(string $tenantId, array $categoryMap): void
    {
        $products = [
            [
                'sku' => 'LG-CITY-001',
                'name' => 'LEGO City Police Station',
                'category' => 'City',
                'description' => 'A fully detailed police station with jail cell, rooftop access, vehicles, and 5 minifigures. Perfect for ages 6+.',
                'price' => 3499.00,
                'cost_price' => 1750.00,
                'variants' => [
                    ['name' => 'Standard Edition', 'sku' => 'LG-CITY-001-V1', 'price_modifier' => 0],
                    ['name' => 'Deluxe Edition w/ Extra Vehicles', 'sku' => 'LG-CITY-001-V2', 'price_modifier' => 800.00],
                ],
            ],
            [
                'sku' => 'LG-STAR-002',
                'name' => 'LEGO Star Wars Millennium Falcon',
                'category' => 'Star Wars',
                'description' => 'Iconic Millennium Falcon replica with detailed interior, rotating turrets, and 7 iconic minifigures including Han Solo and Chewbacca.',
                'price' => 8999.00,
                'cost_price' => 4500.00,
                'variants' => [
                    ['name' => '792-Piece Set', 'sku' => 'LG-STAR-002-V1', 'price_modifier' => 0],
                    ['name' => "UCS Collector's Edition (1,351 pcs)", 'sku' => 'LG-STAR-002-V2', 'price_modifier' => 6000.00],
                ],
            ],
            [
                'sku' => 'LG-TECH-003',
                'name' => 'LEGO Technic Bugatti Chiron',
                'category' => 'Technic',
                'description' => 'A 1:8 scale model of the Bugatti Chiron with functioning W16 engine, gearbox, and rear spoiler. For advanced builders aged 16+.',
                'price' => 19999.00,
                'cost_price' => 9800.00,
                'variants' => [
                    ['name' => 'Standard Build', 'sku' => 'LG-TECH-003-V1', 'price_modifier' => 0],
                    ['name' => 'Special Display Stand Bundle', 'sku' => 'LG-TECH-003-V2', 'price_modifier' => 2500.00],
                ],
            ],
            [
                'sku' => 'LG-NINA-004',
                'name' => 'LEGO NINJAGO Legacy Dragon Pit',
                'category' => 'NINJAGO',
                'description' => "Epic dragon-themed battle arena with 4 dragon figures, 8 minifigures, and over 1,600 pieces. Includes Wu's dragon and the Dragon Pit arena.",
                'price' => 5999.00,
                'cost_price' => 2900.00,
                'variants' => [
                    ['name' => 'Base Set', 'sku' => 'LG-NINA-004-V1', 'price_modifier' => 0],
                    ['name' => 'Limited Edition Gold Dragon Variant', 'sku' => 'LG-NINA-004-V2', 'price_modifier' => 1500.00],
                ],
            ],
            [
                'sku' => 'LG-FRIE-005',
                'name' => 'LEGO Friends Heartlake City Resort',
                'category' => 'Friends',
                'description' => 'A luxurious resort playset with pool, restaurant, hotel rooms, and 6 Friends characters. Features working elevator and light-up elements.',
                'price' => 4799.00,
                'cost_price' => 2300.00,
                'variants' => [
                    ['name' => 'Standard Set', 'sku' => 'LG-FRIE-005-V1', 'price_modifier' => 0],
                    ['name' => 'Resort & Spa Extension Pack', 'sku' => 'LG-FRIE-005-V2', 'price_modifier' => 1200.00],
                ],
            ],
            [
                'sku' => 'LG-CREA-006',
                'name' => 'LEGO Creator 3-in-1 Deep Sea Creatures',
                'category' => 'Creator',
                'description' => 'Build a shark, crab, or angler fish with this 3-in-1 set. Detailed underwater creature designs with articulated parts. Ages 7+.',
                'price' => 1299.00,
                'cost_price' => 620.00,
                'variants' => [
                    ['name' => 'Standard (230 pcs)', 'sku' => 'LG-CREA-006-V1', 'price_modifier' => 0],
                    ['name' => 'Expanded Ocean Bundle (2 sets)', 'sku' => 'LG-CREA-006-V2', 'price_modifier' => 1100.00],
                ],
            ],
            [
                'sku' => 'LG-HAPT-007',
                'name' => 'LEGO Harry Potter Hogwarts Castle',
                'category' => 'Harry Potter',
                'description' => "The iconic Hogwarts Castle with over 6,000 pieces. Features the Great Hall, Dumbledore's office, moving staircases, and 27 minifigures.",
                'price' => 24999.00,
                'cost_price' => 12000.00,
                'variants' => [
                    ['name' => 'Main Castle Set', 'sku' => 'LG-HAPT-007-V1', 'price_modifier' => 0],
                    ['name' => 'Castle + Grounds Expansion', 'sku' => 'LG-HAPT-007-V2', 'price_modifier' => 5000.00],
                ],
            ],
            [
                'sku' => 'LG-ARCH-008',
                'name' => 'LEGO Architecture Eiffel Tower',
                'category' => 'Architecture',
                'description' => 'A stunning 1,002-piece replica of the Eiffel Tower standing over 59 cm tall. Perfect display model for adults. Includes informational booklet.',
                'price' => 8499.00,
                'cost_price' => 4100.00,
                'variants' => [
                    ['name' => 'Standard Edition', 'sku' => 'LG-ARCH-008-V1', 'price_modifier' => 0],
                    ['name' => 'Premium Display Case Bundle', 'sku' => 'LG-ARCH-008-V2', 'price_modifier' => 1500.00],
                ],
            ],
            [
                'sku' => 'LG-DUPS-009',
                'name' => 'LEGO DUPLO My First Animal Brick Box',
                'category' => 'DUPLO',
                'description' => 'Large, easy-to-handle DUPLO bricks featuring colorful animals. Encourages creativity and motor skills for toddlers aged 1.5-3 years.',
                'price' => 1099.00,
                'cost_price' => 520.00,
                'variants' => [
                    ['name' => 'Starter Box (34 pcs)', 'sku' => 'LG-DUPS-009-V1', 'price_modifier' => 0],
                    ['name' => 'Mega Animal Farm Set (80 pcs)', 'sku' => 'LG-DUPS-009-V2', 'price_modifier' => 1100.00],
                ],
            ],
            [
                'sku' => 'LG-MAVE-010',
                'name' => 'LEGO Marvel Avengers Tower',
                'category' => 'Marvel Super Heroes',
                'description' => 'Build the Avengers Tower with Iron Man lab, training room, and rooftop helipad. Includes Iron Man, Thor, Black Widow, and 4 more minifigures.',
                'price' => 6499.00,
                'cost_price' => 3150.00,
                'variants' => [
                    ['name' => 'Tower Set (685 pcs)', 'sku' => 'LG-MAVE-010-V1', 'price_modifier' => 0],
                    ['name' => 'Ultimate Avengers Bundle w/ Quinjet', 'sku' => 'LG-MAVE-010-V2', 'price_modifier' => 3500.00],
                ],
            ],
        ];

        foreach ($products as $data) {
            $product = Product::updateOrCreate(
                ['tenant_id' => $tenantId, 'sku' => $data['sku']],
                [
                    'category_id' => $categoryMap[$data['category']],
                    'name' => $data['name'],
                    'slug' => Str::slug($data['name']),
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'cost_price' => $data['cost_price'],
                    'is_active' => true,
                    'is_food' => false,
                ],
            );

            $variationGroup = VariationGroup::updateOrCreate(
                ['tenant_id' => $tenantId, 'product_id' => $product->id, 'name' => 'Edition'],
                [
                    'sort_order' => 0,
                    'is_required' => true,
                ],
            );

            foreach ($data['variants'] as $index => $variant) {
                VariationOption::updateOrCreate(
                    ['variation_group_id' => $variationGroup->id, 'name' => $variant['name']],
                    [
                        'price_modifier' => $variant['price_modifier'],
                        'sort_order' => $index,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
