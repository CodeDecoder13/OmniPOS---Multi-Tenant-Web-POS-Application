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
    case FoodManufacturing = 'food_manufacturing';
    case BeautySalon = 'beauty_salon';
    case Barbershop = 'barbershop';
    case PetShop = 'pet_shop';
    case Electronics = 'electronics';
    case Laundry = 'laundry';
    case MilkTea = 'milk_tea';
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
            self::FoodManufacturing => 'Food Manufacturing',
            self::BeautySalon => 'Beauty Salon & Spa',
            self::Barbershop => 'Barbershop',
            self::PetShop => 'Pet Shop',
            self::Electronics => 'Electronics & Gadgets',
            self::Laundry => 'Laundry Shop',
            self::MilkTea => 'Milk Tea & Dessert Shop',
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
            self::FoodManufacturing => 'Factory',
            self::BeautySalon => 'Sparkles',
            self::Barbershop => 'Scissors',
            self::PetShop => 'PawPrint',
            self::Electronics => 'Smartphone',
            self::Laundry => 'WashingMachine',
            self::MilkTea => 'CupSoda',
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
            self::FoodManufacturing => [
                ['name' => 'Raw Materials', 'slug' => 'raw-materials', 'description' => 'Raw ingredients and base materials'],
                ['name' => 'Finished Products', 'slug' => 'finished-products', 'description' => 'Ready-to-sell finished food products'],
                ['name' => 'Packaging', 'slug' => 'packaging', 'description' => 'Packaging materials and containers'],
                ['name' => 'Frozen Goods', 'slug' => 'frozen-goods', 'description' => 'Frozen food products'],
                ['name' => 'Beverages', 'slug' => 'beverages', 'description' => 'Manufactured drinks and beverage products'],
                ['name' => 'Condiments & Sauces', 'slug' => 'condiments-sauces', 'description' => 'Sauces, seasonings, and condiment products'],
                ['name' => 'Snacks', 'slug' => 'snacks', 'description' => 'Manufactured snack products'],
                ['name' => 'Supplies', 'slug' => 'supplies', 'description' => 'Production supplies and equipment'],
            ],
            self::BeautySalon => [
                ['name' => 'Hair Services', 'slug' => 'hair-services', 'description' => 'Cuts, color, rebond, and treatments'],
                ['name' => 'Nail Services', 'slug' => 'nail-services', 'description' => 'Manicure, pedicure, and nail art'],
                ['name' => 'Skin & Facial', 'slug' => 'skin-facial', 'description' => 'Facials, peels, and skin treatments'],
                ['name' => 'Massage & Spa', 'slug' => 'massage-spa', 'description' => 'Massage, body scrub, and spa packages'],
                ['name' => 'Waxing & Threading', 'slug' => 'waxing-threading', 'description' => 'Hair removal services'],
                ['name' => 'Makeup', 'slug' => 'makeup', 'description' => 'Makeup services and bridal packages'],
                ['name' => 'Retail Products', 'slug' => 'retail-products', 'description' => 'Shampoo, skincare, and beauty products for sale'],
                ['name' => 'Packages', 'slug' => 'packages', 'description' => 'Bundled service packages and promos'],
            ],
            self::Barbershop => [
                ['name' => 'Haircuts', 'slug' => 'haircuts', 'description' => 'Regular, kids, and premium haircuts'],
                ['name' => 'Shave & Beard', 'slug' => 'shave-beard', 'description' => 'Shaves, beard trims, and grooming'],
                ['name' => 'Hair Color & Treatment', 'slug' => 'hair-color-treatment', 'description' => 'Coloring, hot oil, and treatments'],
                ['name' => 'Packages', 'slug' => 'packages', 'description' => 'Combo services and promos'],
                ['name' => 'Hair Products', 'slug' => 'hair-products', 'description' => 'Pomade, wax, shampoo, and styling products'],
                ['name' => 'Grooming Tools', 'slug' => 'grooming-tools', 'description' => 'Combs, razors, and accessories'],
            ],
            self::PetShop => [
                ['name' => 'Dog Food', 'slug' => 'dog-food', 'description' => 'Dry and wet food for dogs'],
                ['name' => 'Cat Food', 'slug' => 'cat-food', 'description' => 'Dry and wet food for cats'],
                ['name' => 'Treats & Snacks', 'slug' => 'treats-snacks', 'description' => 'Pet treats and chews'],
                ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Collars, leashes, beds, and carriers'],
                ['name' => 'Toys', 'slug' => 'toys', 'description' => 'Toys for dogs, cats, and small pets'],
                ['name' => 'Grooming', 'slug' => 'grooming', 'description' => 'Shampoo, brushes, and grooming services'],
                ['name' => 'Health & Medicine', 'slug' => 'health-medicine', 'description' => 'Vitamins, flea and tick, and meds'],
                ['name' => 'Aquarium & Small Pets', 'slug' => 'aquarium-small-pets', 'description' => 'Fish, bird, and small pet supplies'],
            ],
            self::Electronics => [
                ['name' => 'Phones & Tablets', 'slug' => 'phones-tablets', 'description' => 'Smartphones, tablets, and wearables'],
                ['name' => 'Computers & Laptops', 'slug' => 'computers-laptops', 'description' => 'Laptops, desktops, and peripherals'],
                ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Cases, chargers, cables, and earphones'],
                ['name' => 'Audio', 'slug' => 'audio', 'description' => 'Speakers, headphones, and sound gear'],
                ['name' => 'Gaming', 'slug' => 'gaming', 'description' => 'Consoles, controllers, and games'],
                ['name' => 'Parts & Components', 'slug' => 'parts-components', 'description' => 'Replacement parts and components'],
                ['name' => 'Repair Services', 'slug' => 'repair-services', 'description' => 'Device repair and service jobs'],
                ['name' => 'Power & Batteries', 'slug' => 'power-batteries', 'description' => 'Power banks, batteries, and adapters'],
            ],
            self::Laundry => [
                ['name' => 'Wash & Fold', 'slug' => 'wash-fold', 'description' => 'Wash, dry, and fold by kilo'],
                ['name' => 'Dry Cleaning', 'slug' => 'dry-cleaning', 'description' => 'Dry cleaning per piece'],
                ['name' => 'Ironing & Pressing', 'slug' => 'ironing-pressing', 'description' => 'Ironing and pressing services'],
                ['name' => 'Bulky Items', 'slug' => 'bulky-items', 'description' => 'Comforters, curtains, and blankets'],
                ['name' => 'Add-ons', 'slug' => 'add-ons', 'description' => 'Fabric conditioner, stain removal, and express service'],
                ['name' => 'Supplies', 'slug' => 'supplies', 'description' => 'Detergent, softener, and laundry supplies'],
            ],
            self::MilkTea => [
                ['name' => 'Milk Tea', 'slug' => 'milk-tea', 'description' => 'Classic and flavored milk teas'],
                ['name' => 'Fruit Tea', 'slug' => 'fruit-tea', 'description' => 'Fruit teas and refreshers'],
                ['name' => 'Cheese Cream Series', 'slug' => 'cheese-cream-series', 'description' => 'Cream cheese topped drinks'],
                ['name' => 'Frappes & Smoothies', 'slug' => 'frappes-smoothies', 'description' => 'Blended and frozen drinks'],
                ['name' => 'Coffee', 'slug' => 'coffee', 'description' => 'Hot and iced coffee'],
                ['name' => 'Desserts', 'slug' => 'desserts', 'description' => 'Waffles, cakes, and sweets'],
                ['name' => 'Snacks', 'slug' => 'snacks', 'description' => 'Fries, siomai, and light bites'],
                ['name' => 'Add-ons', 'slug' => 'add-ons', 'description' => 'Pearls, jellies, cream cheese, and toppings'],
            ],
            self::Other => [
                ['name' => 'General', 'slug' => 'general', 'description' => 'General products and items'],
                ['name' => 'Miscellaneous', 'slug' => 'miscellaneous', 'description' => 'Uncategorized and miscellaneous items'],
            ],
        };
    }

    /**
     * What a new product of this business should default to: 'goods', 'service' or 'prepared'
     * (made to order). It only pre-selects the choice on the product form; the owner can change it.
     */
    public function defaultProductKind(): string
    {
        return match ($this) {
            self::BeautySalon, self::Barbershop, self::Laundry => 'service',
            self::Cafe, self::MilkTea, self::Restaurant => 'prepared',
            default => 'goods',
        };
    }

    /**
     * Branch feature switches this kind of business should have ON by default.
     * Everything not listed keeps the normal default. Owners can change any of them in
     * Branches > Feature Settings, so this is a starting point, never a lock.
     *
     * @return array<string, bool>
     */
    public function branchSettings(): array
    {
        return match ($this) {
            self::BeautySalon, self::Barbershop => ['staff_tracking' => true, 'commissions' => true],
            default => [],
        };
    }

    /**
     * The words this kind of business uses, so the app does not say "waiter" to a hair salon.
     *
     * @return array{staff: string, staff_plural: string, service: string}
     */
    public function terms(): array
    {
        return match ($this) {
            self::BeautySalon => ['staff' => 'Stylist', 'staff_plural' => 'Stylists', 'service' => 'Service'],
            self::Barbershop => ['staff' => 'Barber', 'staff_plural' => 'Barbers', 'service' => 'Service'],
            self::Electronics => ['staff' => 'Technician', 'staff_plural' => 'Technicians', 'service' => 'Repair'],
            self::Laundry => ['staff' => 'Attendant', 'staff_plural' => 'Attendants', 'service' => 'Service'],
            self::Restaurant, self::Cafe, self::MilkTea, self::Bar => ['staff' => 'Staff member', 'staff_plural' => 'Staff', 'service' => 'Service'],
            default => ['staff' => 'Staff member', 'staff_plural' => 'Staff', 'service' => 'Service'],
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
