<?php

namespace App\Enums;

enum BusinessType: string
{
    case Grocery = 'grocery';
    case Cafe = 'cafe';
    case Restaurant = 'restaurant';
    case Bar = 'bar';
    case Retail = 'retail';
    case Clothing = 'clothing';
    case Bakery = 'bakery';
    case Pharmacy = 'pharmacy';
    case Hardware = 'hardware';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Grocery => 'Grocery / Convenience Store',
            self::Cafe => 'Cafe / Coffee Shop',
            self::Restaurant => 'Restaurant',
            self::Bar => 'Bar & Pub',
            self::Retail => 'Retail Store',
            self::Clothing => 'Clothing & Accessories',
            self::Bakery => 'Bakery',
            self::Pharmacy => 'Pharmacy / Drugstore',
            self::Hardware => 'Hardware Store',
            self::Other => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Grocery => 'ShoppingCart',
            self::Cafe => 'Coffee',
            self::Restaurant => 'Utensils',
            self::Bar => 'Wine',
            self::Retail => 'Store',
            self::Clothing => 'Shirt',
            self::Bakery => 'Cake',
            self::Pharmacy => 'Pill',
            self::Hardware => 'Wrench',
            self::Other => 'LayoutGrid',
        };
    }

    /**
     * @return array<array{name: string, slug: string, description: string}>
     */
    public function defaultCategories(): array
    {
        return match ($this) {
            self::Grocery => [
                ['name' => 'Beverages', 'slug' => 'beverages', 'description' => 'Drinks, juices, water, and soft drinks'],
                ['name' => 'Snacks', 'slug' => 'snacks', 'description' => 'Chips, crackers, cookies, and other snack items'],
                ['name' => 'Canned Goods', 'slug' => 'canned-goods', 'description' => 'Canned food and preserved items'],
                ['name' => 'Dairy & Eggs', 'slug' => 'dairy-eggs', 'description' => 'Milk, cheese, butter, and eggs'],
                ['name' => 'Bread & Bakery', 'slug' => 'bread-bakery', 'description' => 'Bread, rolls, and baked goods'],
                ['name' => 'Frozen Foods', 'slug' => 'frozen-foods', 'description' => 'Frozen meals, ice cream, and frozen vegetables'],
                ['name' => 'Condiments & Sauces', 'slug' => 'condiments-sauces', 'description' => 'Ketchup, soy sauce, vinegar, and dressings'],
                ['name' => 'Rice & Grains', 'slug' => 'rice-grains', 'description' => 'Rice, pasta, noodles, and cereals'],
                ['name' => 'Personal Care', 'slug' => 'personal-care', 'description' => 'Soap, shampoo, toothpaste, and hygiene products'],
                ['name' => 'Household', 'slug' => 'household', 'description' => 'Cleaning supplies and household essentials'],
            ],
            self::Cafe => [
                ['name' => 'Hot Coffee', 'slug' => 'hot-coffee', 'description' => 'Brewed and espresso-based hot coffee drinks'],
                ['name' => 'Iced Coffee', 'slug' => 'iced-coffee', 'description' => 'Chilled and iced coffee beverages'],
                ['name' => 'Tea', 'slug' => 'tea', 'description' => 'Hot and iced tea selections'],
                ['name' => 'Frappes', 'slug' => 'frappes', 'description' => 'Blended frozen coffee and specialty drinks'],
                ['name' => 'Non-Coffee Drinks', 'slug' => 'non-coffee-drinks', 'description' => 'Smoothies, juices, and chocolate drinks'],
                ['name' => 'Pastries', 'slug' => 'pastries', 'description' => 'Croissants, muffins, and baked pastries'],
                ['name' => 'Sandwiches', 'slug' => 'sandwiches', 'description' => 'Sandwiches and savory snacks'],
                ['name' => 'Cakes & Desserts', 'slug' => 'cakes-desserts', 'description' => 'Sliced cakes, cheesecakes, and desserts'],
                ['name' => 'Add-ons', 'slug' => 'add-ons', 'description' => 'Extra shots, syrups, toppings, and modifiers'],
            ],
            self::Restaurant => [
                ['name' => 'Appetizers', 'slug' => 'appetizers', 'description' => 'Starters and small plates'],
                ['name' => 'Soups', 'slug' => 'soups', 'description' => 'Soup selections'],
                ['name' => 'Salads', 'slug' => 'salads', 'description' => 'Fresh salads and greens'],
                ['name' => 'Main Courses', 'slug' => 'main-courses', 'description' => 'Entrees and main dishes'],
                ['name' => 'Pasta & Noodles', 'slug' => 'pasta-noodles', 'description' => 'Pasta, noodles, and rice dishes'],
                ['name' => 'Seafood', 'slug' => 'seafood', 'description' => 'Fish and seafood dishes'],
                ['name' => 'Desserts', 'slug' => 'desserts', 'description' => 'Sweet treats and after-meal desserts'],
                ['name' => 'Beverages', 'slug' => 'beverages', 'description' => 'Drinks, juices, and refreshments'],
                ['name' => 'Kids Menu', 'slug' => 'kids-menu', 'description' => 'Child-friendly meals and portions'],
                ['name' => 'Sides', 'slug' => 'sides', 'description' => 'Side dishes and extras'],
            ],
            self::Bar => [
                ['name' => 'Beer', 'slug' => 'beer', 'description' => 'Draft, bottled, and craft beers'],
                ['name' => 'Cocktails', 'slug' => 'cocktails', 'description' => 'Signature and classic cocktails'],
                ['name' => 'Wine', 'slug' => 'wine', 'description' => 'Red, white, and sparkling wines'],
                ['name' => 'Spirits', 'slug' => 'spirits', 'description' => 'Whiskey, vodka, rum, gin, and other spirits'],
                ['name' => 'Non-Alcoholic', 'slug' => 'non-alcoholic', 'description' => 'Mocktails, sodas, and juices'],
                ['name' => 'Bar Chow', 'slug' => 'bar-chow', 'description' => 'Finger food and bar snacks'],
                ['name' => 'Pulutan', 'slug' => 'pulutan', 'description' => 'Filipino drinking food and appetizers'],
            ],
            self::Retail => [
                ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Gadgets, accessories, and electronics'],
                ['name' => 'Home & Living', 'slug' => 'home-living', 'description' => 'Home decor and furnishings'],
                ['name' => 'Toys & Games', 'slug' => 'toys-games', 'description' => 'Toys, board games, and entertainment'],
                ['name' => 'Stationery', 'slug' => 'stationery', 'description' => 'Office and school supplies'],
                ['name' => 'Sports & Outdoors', 'slug' => 'sports-outdoors', 'description' => 'Sporting goods and outdoor gear'],
                ['name' => 'Health & Beauty', 'slug' => 'health-beauty', 'description' => 'Personal care and beauty products'],
                ['name' => 'Bags & Luggage', 'slug' => 'bags-luggage', 'description' => 'Bags, backpacks, and travel gear'],
                ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'General accessories and miscellaneous items'],
            ],
            self::Clothing => [
                ['name' => 'Men\'s Wear', 'slug' => 'mens-wear', 'description' => 'Clothing for men'],
                ['name' => 'Women\'s Wear', 'slug' => 'womens-wear', 'description' => 'Clothing for women'],
                ['name' => 'Kids\' Wear', 'slug' => 'kids-wear', 'description' => 'Clothing for children'],
                ['name' => 'Footwear', 'slug' => 'footwear', 'description' => 'Shoes, sandals, and sneakers'],
                ['name' => 'Activewear', 'slug' => 'activewear', 'description' => 'Athletic and sports clothing'],
                ['name' => 'Underwear & Socks', 'slug' => 'underwear-socks', 'description' => 'Undergarments and socks'],
                ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Belts, hats, jewelry, and fashion accessories'],
                ['name' => 'Bags', 'slug' => 'bags', 'description' => 'Handbags, wallets, and pouches'],
            ],
            self::Bakery => [
                ['name' => 'Bread', 'slug' => 'bread', 'description' => 'Loaves, rolls, and artisan breads'],
                ['name' => 'Cakes', 'slug' => 'cakes', 'description' => 'Whole cakes and custom cakes'],
                ['name' => 'Cupcakes', 'slug' => 'cupcakes', 'description' => 'Cupcakes and mini cakes'],
                ['name' => 'Pastries', 'slug' => 'pastries', 'description' => 'Croissants, danishes, and puff pastries'],
                ['name' => 'Cookies & Bars', 'slug' => 'cookies-bars', 'description' => 'Cookies, brownies, and dessert bars'],
                ['name' => 'Pies & Tarts', 'slug' => 'pies-tarts', 'description' => 'Sweet and savory pies and tarts'],
                ['name' => 'Doughnuts', 'slug' => 'doughnuts', 'description' => 'Doughnuts and fried pastries'],
                ['name' => 'Drinks', 'slug' => 'drinks', 'description' => 'Coffee, tea, and beverages'],
            ],
            self::Pharmacy => [
                ['name' => 'Prescription Medicines', 'slug' => 'prescription-medicines', 'description' => 'Prescription-only medications'],
                ['name' => 'Over-the-Counter', 'slug' => 'over-the-counter', 'description' => 'OTC medicines and remedies'],
                ['name' => 'Vitamins & Supplements', 'slug' => 'vitamins-supplements', 'description' => 'Vitamins, minerals, and dietary supplements'],
                ['name' => 'Personal Care', 'slug' => 'personal-care', 'description' => 'Soap, shampoo, and hygiene products'],
                ['name' => 'Baby Care', 'slug' => 'baby-care', 'description' => 'Baby formula, diapers, and baby essentials'],
                ['name' => 'First Aid', 'slug' => 'first-aid', 'description' => 'Bandages, antiseptics, and first aid supplies'],
                ['name' => 'Medical Supplies', 'slug' => 'medical-supplies', 'description' => 'Masks, thermometers, and medical devices'],
                ['name' => 'Beauty & Skincare', 'slug' => 'beauty-skincare', 'description' => 'Skincare, cosmetics, and beauty products'],
                ['name' => 'Health Foods', 'slug' => 'health-foods', 'description' => 'Sugar-free, organic, and health food items'],
            ],
            self::Hardware => [
                ['name' => 'Hand Tools', 'slug' => 'hand-tools', 'description' => 'Hammers, screwdrivers, pliers, and wrenches'],
                ['name' => 'Power Tools', 'slug' => 'power-tools', 'description' => 'Drills, saws, grinders, and power equipment'],
                ['name' => 'Electrical', 'slug' => 'electrical', 'description' => 'Wires, switches, outlets, and electrical supplies'],
                ['name' => 'Plumbing', 'slug' => 'plumbing', 'description' => 'Pipes, fittings, faucets, and plumbing supplies'],
                ['name' => 'Paint & Finishes', 'slug' => 'paint-finishes', 'description' => 'Paints, varnishes, brushes, and rollers'],
                ['name' => 'Fasteners', 'slug' => 'fasteners', 'description' => 'Nails, screws, bolts, and anchors'],
                ['name' => 'Building Materials', 'slug' => 'building-materials', 'description' => 'Cement, lumber, roofing, and construction materials'],
                ['name' => 'Safety Equipment', 'slug' => 'safety-equipment', 'description' => 'Gloves, goggles, helmets, and safety gear'],
                ['name' => 'Garden & Outdoor', 'slug' => 'garden-outdoor', 'description' => 'Garden tools, hoses, and outdoor equipment'],
            ],
            self::Other => [
                ['name' => 'General', 'slug' => 'general', 'description' => 'General products and items'],
                ['name' => 'Miscellaneous', 'slug' => 'miscellaneous', 'description' => 'Uncategorized and miscellaneous items'],
            ],
        };
    }

    /**
     * @return array<array{value: string, label: string, icon: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'icon' => $type->icon(),
        ], self::cases());
    }
}
