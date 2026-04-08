<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import {
    ArrowRight, Sparkles, Check, Menu, X,
    Monitor, ClipboardList, BarChart3, Building2, ShieldCheck, Heart,
    ScanBarcode, Wallet, Receipt, Fingerprint, PackageCheck, RotateCcw,
    TrendingUp, PieChart, LineChart, Activity, KeyRound, Search, History,
    UserPlus, Settings, Play, Rocket,
    ShoppingCart, Coffee, Utensils, Wine, Store, Shirt, Cake, Pill, Wrench, LayoutGrid,
    CreditCard, Printer, LayoutDashboard,
    Tag, Clock, Truck, CalendarDays, Zap, Globe, Boxes, ChefHat, Star, Users,
    FileSpreadsheet, Lock, QrCode, Bell, Timer, Shuffle, TableProperties, Megaphone,
    Cookie,
} from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import type { Plan } from '@/types';

const props = withDefaults(
    defineProps<{
        canRegister: boolean;
        plans: Plan[];
    }>(),
    { canRegister: true },
);

const mobileMenuOpen = ref(false);
const scrolled = ref(false);
const activeFeatureTab = ref(0);

function handleScroll() {
    scrolled.value = window.scrollY > 10;
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        },
        { threshold: 0.1 },
    );

    document.querySelectorAll('[data-animate]').forEach((el) => observer.observe(el));

    // Inject JSON-LD structured data for SEO
    const ldScripts: HTMLScriptElement[] = [];
    [jsonLd, orgJsonLd].forEach((data) => {
        const script = document.createElement('script');
        script.type = 'application/ld+json';
        script.textContent = data;
        document.head.appendChild(script);
        ldScripts.push(script);
    });

    onUnmounted(() => {
        window.removeEventListener('scroll', handleScroll);
        observer.disconnect();
        ldScripts.forEach((s) => s.remove());
    });
});

function scrollTo(id: string) {
    mobileMenuOpen.value = false;
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

// JSON-LD structured data for SEO
const jsonLd = JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    name: 'OmniPOS',
    applicationCategory: 'BusinessApplication',
    operatingSystem: 'Web',
    description: 'All-in-one cloud POS system built for Filipino businesses. Manage sales, inventory, multi-branch operations, and analytics.',
    url: 'https://omnipos.shop',
    offers: {
        '@type': 'AggregateOffer',
        priceCurrency: 'PHP',
        lowPrice: '0',
        offerCount: props.plans.length,
    },
    featureList: [
        'Point of Sale',
        'Inventory Management',
        'Multi-Branch Management',
        'Sales Analytics & Reports',
        'Customer Management',
        'Kitchen Display System',
        'Promotions & Discounts',
        'Role-Based Access Control',
        'Shift Management',
        'Supply Chain Management',
    ],
    screenshot: 'https://omnipos.shop/og-image.png',
    aggregateRating: {
        '@type': 'AggregateRating',
        ratingValue: '4.8',
        ratingCount: '50',
    },
});

const orgJsonLd = JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: 'OmniPOS',
    url: 'https://omnipos.shop',
    logo: 'https://omnipos.shop/favicon.svg',
    description: 'The all-in-one point-of-sale system built for Filipino businesses.',
    contactPoint: {
        '@type': 'ContactPoint',
        contactType: 'customer support',
        availableLanguage: ['English', 'Filipino'],
    },
});

const featureTabs = [
    {
        label: 'POS & Checkout',
        icon: Monitor,
        title: 'Lightning-Fast Point of Sale',
        description: 'Process transactions in seconds with our intuitive POS interface. Designed for speed and accuracy, even during peak hours.',
        items: [
            'Real-time product display with categories',
            'Smart cart management with quantity controls',
            'Multiple payment methods (Cash, Card, GCash, Maya, Bank)',
            'Dine-in, takeout & delivery order types',
            'Flexible discount application',
            'PDF receipt generation & printing',
            'Secure PIN-based operator login',
            'Shift management with cash reconciliation',
        ],
    },
    {
        label: 'Inventory',
        icon: ClipboardList,
        title: 'Complete Inventory Control',
        description: 'Never run out of stock or overorder again. Track every item across all your branches with precision.',
        items: [
            'Per-branch inventory tracking',
            'Low-stock threshold alerts',
            '6 adjustment types (restock, damage, return, etc.)',
            'Full audit trail for all changes',
        ],
    },
    {
        label: 'Reports & Analytics',
        icon: BarChart3,
        title: 'Data-Driven Decisions',
        description: 'Understand your business performance with comprehensive reports that turn raw data into actionable insights.',
        items: [
            'Sales trends & revenue analytics',
            'Product performance rankings',
            'Payment method analysis',
            'Operator performance tracking',
            'Branch comparison reports',
            'Custom date range filtering',
        ],
    },
    {
        label: 'Multi-Branch',
        icon: Building2,
        title: 'Centralized Multi-Branch Management',
        description: 'Scale your business confidently. Manage all your locations from a single, powerful dashboard.',
        items: [
            'Centralized branch management',
            'Instant branch switching',
            'Per-branch inventory & shift tracking',
            'Per-branch order management',
            'Cross-branch comparison reports',
        ],
    },
    {
        label: 'Users & Security',
        icon: ShieldCheck,
        title: 'Enterprise-Grade Security',
        description: 'Protect your business with granular access controls and multiple layers of security.',
        items: [
            '50+ granular permission controls',
            'Custom role creation & assignment',
            'PIN-based POS operator security',
            'Two-factor authentication (2FA)',
            'Shift-based cash reconciliation',
        ],
    },
    {
        label: 'Customer Management',
        icon: Heart,
        title: 'Know Your Customers',
        description: 'Build lasting relationships with your customers by tracking their preferences and purchase history.',
        items: [
            'Customer database management',
            'Full order history per customer',
            'Quick search in POS checkout',
            'Customer notes & contact info',
        ],
    },
    {
        label: 'Kitchen Display',
        icon: ChefHat,
        title: 'Real-Time Kitchen Display System',
        description: 'Streamline your kitchen operations with live order queues, status tracking, and instant communication between front-of-house and kitchen staff.',
        items: [
            'Real-time order queue display',
            'Status workflow (new → preparing → ready → served)',
            'KOT printing to kitchen printers',
            'Sound alerts for new orders',
        ],
    },
    {
        label: 'Promotions',
        icon: Tag,
        title: 'Promotions & Discount Engine',
        description: 'Drive sales with flexible promotions. Create percentage or fixed discounts, buy-X-get-Y deals, and promo codes with full control over limits and scheduling.',
        items: [
            'Percentage, fixed amount & buy-X-get-Y promos',
            'Promo code generation & validation',
            'Minimum order & usage limit controls',
            'Date-range scheduling for campaigns',
        ],
    },
    {
        label: 'Supply Chain',
        icon: Truck,
        title: 'End-to-End Supply Chain Management',
        description: 'Manage your entire supply chain from purchase orders to receiving. Track suppliers, create orders, and auto-update inventory on delivery.',
        items: [
            'Purchase orders (draft → sent → received)',
            'Inter-branch stock transfers',
            'Supplier management & directory',
            'Receiving with auto inventory update',
        ],
    },
    {
        label: 'Shifts & Scheduling',
        icon: Clock,
        title: 'Shift Management & Scheduling',
        description: 'Keep your operations running smoothly with comprehensive shift management, cash reconciliation, and employee scheduling tools.',
        items: [
            'Shift open/close with cash count',
            'Cash reconciliation & variance tracking',
            'Operator performance metrics',
            'Employee shift scheduling',
        ],
    },
];

const showcases = [
    {
        title: 'POS & Checkout Experience',
        description: 'A beautiful, intuitive point-of-sale interface that your staff will love. Process orders quickly with real-time product browsing, smart cart management, and flexible payment options.',
        features: ['Category-based product browsing', 'Real-time cart updates', 'Multiple payment splitting', 'One-tap order completion'],
        reverse: false,
    },
    {
        title: 'Reports & Analytics Dashboard',
        description: 'Make smarter business decisions with comprehensive analytics. Track sales trends, compare branch performance, and identify your best-selling products — all in real time.',
        features: ['Visual sales trend charts', 'Product performance rankings', 'Branch comparison insights', 'Export-ready reports'],
        reverse: true,
    },
    {
        title: 'Inventory Management System',
        description: 'Stay on top of your stock levels across all branches. Get alerts before items run out, track every adjustment, and maintain a complete audit trail.',
        features: ['Real-time stock levels', 'Low-stock threshold alerts', 'Adjustment type tracking', 'Per-branch inventory view'],
        reverse: false,
    },
];

const howItWorks = [
    { icon: UserPlus, title: 'Create Account', description: 'Sign up for free in under a minute. No credit card required.' },
    { icon: Settings, title: 'Setup Your Store', description: 'Add products, set up branches, and configure your team roles.' },
    { icon: Play, title: 'Start Selling', description: 'Open your POS, process your first sale, and print receipts.' },
    { icon: Rocket, title: 'Grow & Scale', description: 'Add branches, analyze reports, and expand your business.' },
];

const businessTypes = [
    { icon: ShoppingCart, name: 'Retail Store', count: '500+ products' },
    { icon: Coffee, name: 'Coffee Shop', count: 'Beverages & food' },
    { icon: Utensils, name: 'Restaurant', count: 'Dine-in & takeout' },
    { icon: Wine, name: 'Bar & Lounge', count: 'Drinks & events' },
    { icon: Store, name: 'Grocery', count: 'Fresh & packaged' },
    { icon: Shirt, name: 'Clothing', count: 'Apparel & accessories' },
    { icon: Cake, name: 'Bakery', count: 'Pastries & cakes' },
    { icon: Pill, name: 'Pharmacy', count: 'Health & wellness' },
    { icon: Wrench, name: 'Hardware', count: 'Tools & supplies' },
    { icon: LayoutGrid, name: 'General Store', count: 'Multi-category' },
];

// --- Cookie consent ---
const cookiesDecided = localStorage.getItem('omnipos_cookies_accepted');
const showCookieModal = ref(!cookiesDecided);

function acceptCookies() {
    localStorage.setItem('omnipos_cookies_accepted', 'accepted');
    showCookieModal.value = false;
}

function declineCookies() {
    localStorage.setItem('omnipos_cookies_accepted', 'declined');
    showCookieModal.value = false;
}
</script>

<template>
    <Head title="OmniPOS — All-in-One POS System for Philippine Businesses | Free POS Software">
        <meta name="description" content="OmniPOS is the all-in-one cloud POS system built for Filipino businesses. Manage sales, inventory, multi-branch operations, and analytics. Free plan available — no credit card required." />
        <meta name="keywords" content="POS system Philippines, point of sale software, cloud POS, free POS system, multi-branch POS, inventory management, sales analytics, Filipino business, restaurant POS, retail POS" />
        <meta name="author" content="OmniPOS" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="https://omnipos.shop/" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://omnipos.shop/" />
        <meta property="og:title" content="OmniPOS — All-in-One POS System for Philippine Businesses" />
        <meta property="og:description" content="Manage sales, inventory, multi-branch operations, and analytics with the most complete POS solution built for Filipino businesses. Start free today." />
        <meta property="og:image" content="https://omnipos.shop/og-image.png" />
        <meta property="og:site_name" content="OmniPOS" />
        <meta property="og:locale" content="en_PH" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="OmniPOS — All-in-One POS System for Philippine Businesses" />
        <meta name="twitter:description" content="Manage sales, inventory, multi-branch operations, and analytics with the most complete POS solution built for Filipino businesses." />
        <meta name="twitter:image" content="https://omnipos.shop/og-image.png" />

        <!-- Additional SEO -->
        <meta name="theme-color" content="#0d9488" />
        <meta name="application-name" content="OmniPOS" />
        <meta name="mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-title" content="OmniPOS" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="default" />

        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100">
        <!-- ==================== 1. NAVBAR ==================== -->
        <nav
            aria-label="Main navigation"
            class="sticky top-0 z-50 border-b backdrop-blur-lg transition-shadow duration-300"
            :class="scrolled
                ? 'border-gray-200 bg-white/80 shadow-lg dark:border-gray-800 dark:bg-gray-950/80'
                : 'border-transparent bg-white/60 dark:bg-gray-950/60'"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-2" aria-label="OmniPOS — Go to homepage">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-600">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold">OmniPOS</span>
                </a>

                <!-- Desktop nav -->
                <div class="hidden items-center gap-8 md:flex">
                    <button @click="scrollTo('features')" class="text-sm text-gray-600 transition hover:text-teal-600 dark:text-gray-400 dark:hover:text-teal-400">Features</button>
                    <button @click="scrollTo('how-it-works')" class="text-sm text-gray-600 transition hover:text-teal-600 dark:text-gray-400 dark:hover:text-teal-400">How It Works</button>
                    <button @click="scrollTo('pricing')" class="text-sm text-gray-600 transition hover:text-teal-600 dark:text-gray-400 dark:hover:text-teal-400">Pricing</button>
                    <Link href="/about" class="text-sm text-gray-600 transition hover:text-teal-600 dark:text-gray-400 dark:hover:text-teal-400">About</Link>
                </div>

                <div class="hidden items-center gap-3 md:flex">
                    <Link
                        v-if="$page.props.auth.user"
                        href="/dashboard"
                        class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link href="/login" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 transition hover:text-teal-600 dark:text-gray-300 dark:hover:text-teal-400">
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            href="/register"
                            class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700"
                        >
                            Get Started
                        </Link>
                    </template>
                </div>

                <!-- Mobile hamburger -->
                <button class="md:hidden" @click="mobileMenuOpen = !mobileMenuOpen">
                    <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
            </div>

            <!-- Mobile menu -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="mobileMenuOpen" class="border-t border-gray-200 px-4 py-4 md:hidden dark:border-gray-800">
                    <div class="flex flex-col gap-3">
                        <button @click="scrollTo('features')" class="text-left text-sm text-gray-600 hover:text-teal-600 dark:text-gray-400">Features</button>
                        <button @click="scrollTo('how-it-works')" class="text-left text-sm text-gray-600 hover:text-teal-600 dark:text-gray-400">How It Works</button>
                        <button @click="scrollTo('pricing')" class="text-left text-sm text-gray-600 hover:text-teal-600 dark:text-gray-400">Pricing</button>
                        <Link href="/about" class="text-left text-sm text-gray-600 hover:text-teal-600 dark:text-gray-400">About</Link>
                        <hr class="border-gray-200 dark:border-gray-800" />
                        <Link v-if="$page.props.auth.user" href="/dashboard" class="text-sm font-medium text-teal-600">Dashboard</Link>
                        <template v-else>
                            <Link href="/login" class="text-sm text-gray-700 dark:text-gray-300">Log in</Link>
                            <Link v-if="canRegister" href="/register" class="rounded-lg bg-teal-600 px-4 py-2 text-center text-sm font-medium text-white">Get Started</Link>
                        </template>
                    </div>
                </div>
            </Transition>
        </nav>

        <!-- ==================== 2. HERO SECTION ==================== -->
        <section class="relative overflow-hidden" aria-label="Hero — OmniPOS overview">
            <!-- Animated gradient background -->
            <div class="hero-bg absolute inset-0"></div>
            <div class="absolute inset-0 bg-[linear-gradient(rgba(0,0,0,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(0,0,0,0.03)_1px,transparent_1px)] bg-[size:4rem_4rem] dark:bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)]"></div>

            <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <!-- Left: Text content -->
                    <div class="text-center lg:text-left" data-animate>
                        <span class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-4 py-1.5 text-sm font-medium text-teal-700 dark:border-teal-800 dark:bg-teal-900/30 dark:text-teal-300">
                            <Sparkles class="h-4 w-4" />
                            Built for Filipino Businesses
                        </span>

                        <h1 class="mt-6 text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                            The All-in-One
                            <span class="bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent dark:from-teal-400 dark:to-cyan-400">
                                POS System
                            </span>
                            for Your Business
                        </h1>

                        <p class="mx-auto mt-6 max-w-xl text-lg text-gray-600 lg:mx-0 dark:text-gray-400">
                            Manage your store, track inventory, process sales, and grow your business with the most complete
                            point-of-sale solution built for Philippine businesses.
                        </p>

                        <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row lg:justify-start">
                            <Link
                                v-if="canRegister"
                                href="/register"
                                class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-8 py-3 text-base font-semibold text-white shadow-lg shadow-teal-600/25 transition hover:bg-teal-700 hover:shadow-xl hover:shadow-teal-600/30"
                            >
                                Start Free Trial
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                            <button
                                @click="scrollTo('features')"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-8 py-3 text-base font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900"
                            >
                                See All Features
                            </button>
                        </div>

                        <!-- Floating stat badges -->
                        <div class="mt-10 flex flex-wrap justify-center gap-4 lg:justify-start">
                            <div class="flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 text-sm font-medium shadow-md backdrop-blur dark:bg-gray-800/80">
                                <Boxes class="h-4 w-4 text-teal-600" />
                                17+ Modules
                            </div>
                            <div class="flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 text-sm font-medium shadow-md backdrop-blur dark:bg-gray-800/80">
                                <Wallet class="h-4 w-4 text-teal-600" />
                                6 Payment Methods
                            </div>
                            <div class="flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 text-sm font-medium shadow-md backdrop-blur dark:bg-gray-800/80">
                                <ShieldCheck class="h-4 w-4 text-teal-600" />
                                50+ Permissions
                            </div>
                        </div>
                    </div>

                    <!-- Right: POS Terminal Mockup -->
                    <div class="hidden lg:block" data-animate>
                        <div class="pos-mockup rounded-2xl border border-gray-200/60 bg-white p-1 shadow-2xl dark:border-gray-700/60 dark:bg-gray-900">
                            <!-- Browser chrome -->
                            <div class="flex items-center gap-2 rounded-t-xl border-b border-gray-100 bg-gray-50 px-4 py-2.5 dark:border-gray-800 dark:bg-gray-800/50">
                                <div class="flex gap-1.5">
                                    <div class="h-3 w-3 rounded-full bg-red-400"></div>
                                    <div class="h-3 w-3 rounded-full bg-yellow-400"></div>
                                    <div class="h-3 w-3 rounded-full bg-green-400"></div>
                                </div>
                                <div class="mx-auto rounded-md bg-gray-200 px-4 py-1 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">omnipos.shop/pos</div>
                            </div>
                            <!-- POS body -->
                            <div class="flex gap-3 p-4">
                                <!-- Products side -->
                                <div class="flex-1">
                                    <!-- Category pills -->
                                    <div class="mb-3 flex gap-2">
                                        <div class="rounded-full bg-teal-600 px-3 py-1 text-xs text-white">All</div>
                                        <div class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-400">Coffee</div>
                                        <div class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-400">Food</div>
                                        <div class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-400">Drinks</div>
                                    </div>
                                    <!-- Product grid -->
                                    <div class="grid grid-cols-3 gap-2">
                                        <div v-for="i in 6" :key="i" class="rounded-lg border border-gray-100 p-2 dark:border-gray-800">
                                            <div class="mb-1.5 h-12 rounded bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-700"></div>
                                            <div class="h-2 w-3/4 rounded bg-gray-200 dark:bg-gray-700"></div>
                                            <div class="mt-1 h-2 w-1/2 rounded bg-teal-200 dark:bg-teal-800"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Cart sidebar -->
                                <div class="w-36 rounded-lg border border-gray-100 bg-gray-50/50 p-2.5 dark:border-gray-800 dark:bg-gray-800/30">
                                    <div class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">Cart (3)</div>
                                    <div v-for="i in 3" :key="i" class="mb-2 flex items-center gap-2">
                                        <div class="h-6 w-6 rounded bg-gray-200 dark:bg-gray-700"></div>
                                        <div class="flex-1">
                                            <div class="h-1.5 w-full rounded bg-gray-300 dark:bg-gray-600"></div>
                                            <div class="mt-1 h-1.5 w-1/2 rounded bg-gray-200 dark:bg-gray-700"></div>
                                        </div>
                                    </div>
                                    <div class="mt-3 border-t border-gray-200 pt-2 dark:border-gray-700">
                                        <div class="flex justify-between text-xs">
                                            <span class="text-gray-500">Total</span>
                                            <span class="font-bold text-gray-800 dark:text-gray-200">₱385.00</span>
                                        </div>
                                    </div>
                                    <div class="mt-2 rounded-md bg-teal-600 py-1.5 text-center text-xs font-semibold text-white">Charge</div>
                                </div>
                            </div>
                            <!-- Floating badges -->
                            <div class="badge-float-1 absolute -right-3 top-16 rounded-lg border border-green-200 bg-white px-3 py-1.5 text-xs font-medium shadow-lg dark:border-green-800 dark:bg-gray-800">
                                <span class="text-green-600">✓</span> Payment received
                            </div>
                            <div class="badge-float-2 absolute -left-3 bottom-20 rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-medium shadow-lg dark:border-blue-800 dark:bg-gray-800">
                                <span class="text-blue-600">📊</span> 12 orders today
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== 3. STATS BAR ==================== -->
        <section class="border-y border-gray-200 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-900/50">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 py-10 sm:px-6 md:grid-cols-4 lg:px-8" data-animate>
                <div class="text-center">
                    <div class="text-3xl font-extrabold text-teal-600 sm:text-4xl">17+</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">Modules</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-extrabold text-teal-600 sm:text-4xl">50+</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">Permissions</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-extrabold text-teal-600 sm:text-4xl">8</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">Report Types</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-extrabold text-teal-600 sm:text-4xl">6</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">Payment Methods</div>
                </div>
            </div>
        </section>

        <!-- ==================== 4. FEATURES TABBED SECTION ==================== -->
        <section id="features" class="py-20 sm:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center" data-animate>
                    <h2 class="text-3xl font-bold sm:text-4xl">Everything You Need to Run Your Business</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-400">
                        From small sari-sari stores to multi-branch enterprises, OmniPOS scales with your business.
                    </p>
                </div>

                <!-- Tab pills -->
                <div class="mt-12 flex gap-2 overflow-x-auto pb-2 sm:flex-wrap sm:justify-center" data-animate>
                    <button
                        v-for="(tab, idx) in featureTabs"
                        :key="tab.label"
                        @click="activeFeatureTab = idx"
                        class="flex shrink-0 items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition"
                        :class="activeFeatureTab === idx
                            ? 'border-teal-600 bg-teal-600 text-white shadow-md'
                            : 'border-gray-200 bg-white text-gray-600 hover:border-teal-300 hover:text-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-teal-600'"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Tab content -->
                <div class="relative mt-12 min-h-[340px]">
                    <Transition
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="opacity-0 translate-y-4"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-200 ease-in absolute inset-0"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-4"
                        mode="out-in"
                    >
                        <div :key="activeFeatureTab" class="grid items-center gap-10 lg:grid-cols-2">
                            <!-- Left: info -->
                            <div>
                                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                                    <component :is="featureTabs[activeFeatureTab].icon" class="h-6 w-6" />
                                </div>
                                <h3 class="text-2xl font-bold">{{ featureTabs[activeFeatureTab].title }}</h3>
                                <p class="mt-3 text-gray-600 dark:text-gray-400">{{ featureTabs[activeFeatureTab].description }}</p>
                                <ul class="mt-6 space-y-3">
                                    <li v-for="item in featureTabs[activeFeatureTab].items" :key="item" class="flex items-start gap-3 text-sm">
                                        <Check class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                                        <span class="text-gray-700 dark:text-gray-300">{{ item }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Right: illustrative mockup (hidden on mobile) -->
                            <div class="hidden lg:block">
                                <!-- POS mockup for tab 0 -->
                                <div v-if="activeFeatureTab === 0" class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/50">
                                    <div class="mb-4 flex gap-2">
                                        <div class="rounded-full bg-teal-600 px-3 py-1 text-xs text-white">All Items</div>
                                        <div class="rounded-full bg-gray-200 px-3 py-1 text-xs dark:bg-gray-700">Beverages</div>
                                        <div class="rounded-full bg-gray-200 px-3 py-1 text-xs dark:bg-gray-700">Snacks</div>
                                    </div>
                                    <div class="grid grid-cols-4 gap-3">
                                        <div v-for="i in 8" :key="i" class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                            <div class="mb-2 h-16 rounded bg-gradient-to-br from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20"></div>
                                            <div class="h-2 w-3/4 rounded bg-gray-300 dark:bg-gray-600"></div>
                                            <div class="mt-1.5 h-2 w-1/2 rounded bg-teal-300 dark:bg-teal-700"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Inventory mockup for tab 1 -->
                                <div v-else-if="activeFeatureTab === 1" class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/50">
                                    <div class="space-y-3">
                                        <div v-for="(item, i) in ['Espresso Beans', 'Paper Cups (L)', 'Vanilla Syrup', 'Milk (1L)']" :key="i" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                                            <span class="text-sm font-medium">{{ item }}</span>
                                            <div class="flex items-center gap-3">
                                                <div class="h-2 w-20 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                                    <div class="h-full rounded-full" :class="i === 1 ? 'w-1/5 bg-red-500' : i === 3 ? 'w-2/5 bg-yellow-500' : 'w-4/5 bg-teal-500'" ></div>
                                                </div>
                                                <span class="text-xs font-medium" :class="i === 1 ? 'text-red-600' : i === 3 ? 'text-yellow-600' : 'text-teal-600'">
                                                    {{ i === 1 ? 'Low' : i === 3 ? 'Medium' : 'Good' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reports mockup for tab 2 -->
                                <div v-else-if="activeFeatureTab === 2" class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/50">
                                    <div class="mb-4 grid grid-cols-3 gap-3">
                                        <div v-for="(stat, i) in [{ label: 'Revenue', value: '₱48,250' }, { label: 'Orders', value: '156' }, { label: 'Avg Order', value: '₱309' }]" :key="i" class="rounded-lg border border-gray-200 bg-white p-3 text-center dark:border-gray-700 dark:bg-gray-800">
                                            <div class="text-xs text-gray-500">{{ stat.label }}</div>
                                            <div class="mt-1 text-lg font-bold text-gray-800 dark:text-gray-200">{{ stat.value }}</div>
                                        </div>
                                    </div>
                                    <!-- Bar chart -->
                                    <div class="flex items-end justify-between gap-2 px-2 pt-4" style="height: 120px">
                                        <div v-for="(h, i) in [40, 60, 45, 80, 70, 95, 65]" :key="i" class="flex-1 rounded-t bg-gradient-to-t from-teal-600 to-teal-400 dark:from-teal-700 dark:to-teal-500" :style="{ height: h + '%' }"></div>
                                    </div>
                                    <div class="mt-2 flex justify-between px-2 text-xs text-gray-400">
                                        <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                                    </div>
                                </div>

                                <!-- Multi-branch mockup for tab 3 -->
                                <div v-else-if="activeFeatureTab === 3" class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/50">
                                    <div class="space-y-3">
                                        <div v-for="(branch, i) in [{ name: 'Main Branch - Manila', sales: '₱32,400', status: 'Active' }, { name: 'Branch 2 - Cebu', sales: '₱18,750', status: 'Active' }, { name: 'Branch 3 - Davao', sales: '₱12,100', status: 'Active' }]" :key="i" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3.5 dark:border-gray-700 dark:bg-gray-800">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-900/30">
                                                    <Building2 class="h-4 w-4 text-teal-600" />
                                                </div>
                                                <span class="text-sm font-medium">{{ branch.name }}</span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ branch.sales }}</span>
                                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">{{ branch.status }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security mockup for tab 4 -->
                                <div v-else-if="activeFeatureTab === 4" class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/50">
                                    <div class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Role: Store Manager</div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div v-for="perm in ['View Products', 'Edit Products', 'Manage Orders', 'View Reports', 'Manage Staff', 'Adjust Inventory', 'Apply Discounts', 'Void Orders']" :key="perm" class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-800">
                                            <Check class="h-3.5 w-3.5 text-teal-600" />
                                            {{ perm }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer mockup for tab 5 -->
                                <div v-else-if="activeFeatureTab === 5" class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/50">
                                    <div class="space-y-3">
                                        <div v-for="(cust, i) in [{ name: 'Maria Santos', orders: 24, total: '₱8,450' }, { name: 'Juan dela Cruz', orders: 18, total: '₱6,200' }, { name: 'Ana Reyes', orders: 12, total: '₱4,100' }]" :key="i" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3.5 dark:border-gray-700 dark:bg-gray-800">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700 dark:bg-teal-900/30 dark:text-teal-400">{{ cust.name.charAt(0) }}</div>
                                                <div>
                                                    <div class="text-sm font-medium">{{ cust.name }}</div>
                                                    <div class="text-xs text-gray-500">{{ cust.orders }} orders</div>
                                                </div>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ cust.total }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kitchen Display mockup for tab 6 -->
                                <div v-else-if="activeFeatureTab === 6" class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/50">
                                    <div class="mb-4 flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Kitchen Orders</span>
                                        <div class="flex gap-2">
                                            <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">3 Pending</span>
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">2 Preparing</span>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <div v-for="(order, i) in [
                                            { id: '#1042', items: 'Latte, Croissant', time: '2m ago', status: 'New', color: 'yellow' },
                                            { id: '#1041', items: 'Americano x2, Muffin', time: '5m ago', status: 'Preparing', color: 'blue' },
                                            { id: '#1040', items: 'Cappuccino, Sandwich', time: '8m ago', status: 'Ready', color: 'green' },
                                        ]" :key="i" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ order.id }}</span>
                                                    <span class="text-xs text-gray-400">{{ order.time }}</span>
                                                </div>
                                                <div class="mt-0.5 text-xs text-gray-500">{{ order.items }}</div>
                                            </div>
                                            <span
                                                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                :class="{
                                                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': order.color === 'yellow',
                                                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': order.color === 'blue',
                                                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': order.color === 'green',
                                                }"
                                            >{{ order.status }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Promotions mockup for tab 7 -->
                                <div v-else-if="activeFeatureTab === 7" class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/50">
                                    <div class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Active Promotions</div>
                                    <div class="space-y-3">
                                        <div v-for="(promo, i) in [
                                            { name: 'Weekend Special', type: '20% OFF', code: 'WKND20', usage: '45/100', active: true },
                                            { name: 'Buy 2 Get 1 Free', type: 'BOGO', code: 'B2G1', usage: '28/50', active: true },
                                            { name: 'New Customer', type: '₱50 OFF', code: 'NEW50', usage: '92/200', active: true },
                                        ]" :key="i" class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                                                        <Tag class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ promo.name }}</div>
                                                        <div class="text-xs text-gray-500">Code: {{ promo.code }}</div>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ promo.type }}</div>
                                                    <div class="text-xs text-gray-400">{{ promo.usage }} used</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Supply Chain mockup for tab 8 -->
                                <div v-else-if="activeFeatureTab === 8" class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/50">
                                    <div class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Purchase Orders</div>
                                    <div class="space-y-3">
                                        <div v-for="(po, i) in [
                                            { id: 'PO-2024-001', supplier: 'Metro Supplies Co.', items: 12, total: '₱24,500', status: 'Received', color: 'green' },
                                            { id: 'PO-2024-002', supplier: 'Fresh Farms Inc.', items: 8, total: '₱18,200', status: 'Sent', color: 'blue' },
                                            { id: 'PO-2024-003', supplier: 'PackRight Trading', items: 5, total: '₱6,800', status: 'Draft', color: 'gray' },
                                        ]" :key="i" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ po.id }}</span>
                                                    <span class="text-xs text-gray-400">{{ po.items }} items</span>
                                                </div>
                                                <div class="mt-0.5 text-xs text-gray-500">{{ po.supplier }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ po.total }}</div>
                                                <span
                                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                    :class="{
                                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': po.color === 'green',
                                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': po.color === 'blue',
                                                        'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400': po.color === 'gray',
                                                    }"
                                                >{{ po.status }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Shifts mockup for tab 9 -->
                                <div v-else class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/50">
                                    <div class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Today's Shifts</div>
                                    <div class="space-y-3">
                                        <div v-for="(shift, i) in [
                                            { operator: 'Maria S.', time: '6:00 AM - 2:00 PM', sales: '₱12,450', status: 'Active', color: 'green' },
                                            { operator: 'Juan D.', time: '2:00 PM - 10:00 PM', sales: '₱8,200', status: 'Active', color: 'green' },
                                            { operator: 'Ana R.', time: '6:00 AM - 2:00 PM', sales: '₱9,800', status: 'Closed', color: 'gray' },
                                        ]" :key="i" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700 dark:bg-teal-900/30 dark:text-teal-400">{{ shift.operator.charAt(0) }}</div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ shift.operator }}</div>
                                                    <div class="text-xs text-gray-500">{{ shift.time }}</div>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ shift.sales }}</div>
                                                <span
                                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                    :class="{
                                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': shift.color === 'green',
                                                        'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400': shift.color === 'gray',
                                                    }"
                                                >{{ shift.status }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </section>

        <!-- ==================== 5. SHOWCASE: POS & CHECKOUT ==================== -->
        <section class="bg-gray-50/50 py-20 dark:bg-gray-900/30">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-2" data-animate>
                    <!-- Left: Text -->
                    <div>
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <Monitor class="h-6 w-6" />
                        </div>
                        <h2 class="text-3xl font-bold">{{ showcases[0].title }}</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">{{ showcases[0].description }}</p>
                        <ul class="mt-6 space-y-3">
                            <li v-for="f in showcases[0].features" :key="f" class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                                <Check class="h-4 w-4 shrink-0 text-teal-600" />
                                {{ f }}
                            </li>
                        </ul>
                    </div>
                    <!-- Right: POS Mockup -->
                    <div class="hidden lg:block">
                        <div class="rounded-2xl border border-gray-200 bg-white p-1 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                            <div class="flex items-center gap-2 rounded-t-xl border-b border-gray-100 bg-gray-50 px-4 py-2.5 dark:border-gray-800 dark:bg-gray-800/50">
                                <div class="flex gap-1.5">
                                    <div class="h-3 w-3 rounded-full bg-red-400"></div>
                                    <div class="h-3 w-3 rounded-full bg-yellow-400"></div>
                                    <div class="h-3 w-3 rounded-full bg-green-400"></div>
                                </div>
                            </div>
                            <div class="flex gap-4 p-5">
                                <div class="flex-1">
                                    <div class="mb-3 flex gap-2">
                                        <div class="rounded-full bg-teal-600 px-3 py-1 text-xs text-white">All</div>
                                        <div class="rounded-full bg-gray-100 px-3 py-1 text-xs dark:bg-gray-800">Coffee</div>
                                        <div class="rounded-full bg-gray-100 px-3 py-1 text-xs dark:bg-gray-800">Pastries</div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div v-for="i in 9" :key="i" class="rounded-lg border border-gray-100 p-2 dark:border-gray-800">
                                            <div class="mb-2 h-14 rounded bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-700"></div>
                                            <div class="h-2 w-3/4 rounded bg-gray-200 dark:bg-gray-700"></div>
                                            <div class="mt-1 h-2 w-1/2 rounded bg-teal-200 dark:bg-teal-800"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-40 rounded-lg border border-gray-100 bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-800/30">
                                    <div class="mb-3 text-xs font-semibold text-gray-700 dark:text-gray-300">Cart (4 items)</div>
                                    <div v-for="i in 4" :key="i" class="mb-2 flex items-center gap-2">
                                        <div class="h-5 w-5 rounded bg-gray-200 dark:bg-gray-700"></div>
                                        <div class="flex-1">
                                            <div class="h-1.5 rounded bg-gray-300 dark:bg-gray-600"></div>
                                        </div>
                                    </div>
                                    <div class="mt-4 border-t border-dashed border-gray-200 pt-3 dark:border-gray-700">
                                        <div class="flex justify-between text-xs font-bold">
                                            <span>Total</span>
                                            <span>₱520.00</span>
                                        </div>
                                    </div>
                                    <div class="mt-3 rounded-md bg-teal-600 py-2 text-center text-xs font-bold text-white">Charge ₱520.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== 6. SHOWCASE: REPORTS ==================== -->
        <section class="py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-2" data-animate>
                    <!-- Left: Report Dashboard Mockup -->
                    <div class="hidden lg:block order-1 lg:order-none">
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                            <!-- Stat cards -->
                            <div class="mb-6 grid grid-cols-3 gap-3">
                                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-center dark:border-gray-800 dark:bg-gray-800/50">
                                    <TrendingUp class="mx-auto h-5 w-5 text-teal-600" />
                                    <div class="mt-2 text-lg font-bold">₱128K</div>
                                    <div class="text-xs text-gray-500">Revenue</div>
                                </div>
                                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-center dark:border-gray-800 dark:bg-gray-800/50">
                                    <ShoppingCart class="mx-auto h-5 w-5 text-blue-600" />
                                    <div class="mt-2 text-lg font-bold">487</div>
                                    <div class="text-xs text-gray-500">Orders</div>
                                </div>
                                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-center dark:border-gray-800 dark:bg-gray-800/50">
                                    <Activity class="mx-auto h-5 w-5 text-green-600" />
                                    <div class="mt-2 text-lg font-bold">+23%</div>
                                    <div class="text-xs text-gray-500">Growth</div>
                                </div>
                            </div>
                            <!-- Bar chart -->
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/50">
                                <div class="mb-3 text-xs font-semibold text-gray-600 dark:text-gray-400">Weekly Sales</div>
                                <div class="flex items-end justify-between gap-2" style="height: 100px">
                                    <div v-for="(h, i) in [50, 70, 40, 90, 75, 100, 60]" :key="i" class="flex-1 rounded-t bg-gradient-to-t from-teal-600 to-teal-400 transition-all dark:from-teal-700 dark:to-teal-500" :style="{ height: h + '%' }"></div>
                                </div>
                                <div class="mt-2 flex justify-between text-xs text-gray-400">
                                    <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Right: Text -->
                    <div>
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            <BarChart3 class="h-6 w-6" />
                        </div>
                        <h2 class="text-3xl font-bold">{{ showcases[1].title }}</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">{{ showcases[1].description }}</p>
                        <ul class="mt-6 space-y-3">
                            <li v-for="f in showcases[1].features" :key="f" class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                                <Check class="h-4 w-4 shrink-0 text-blue-600" />
                                {{ f }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== 7. SHOWCASE: INVENTORY ==================== -->
        <section class="bg-gray-50/50 py-20 dark:bg-gray-900/30">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-2" data-animate>
                    <!-- Left: Text -->
                    <div>
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                            <PackageCheck class="h-6 w-6" />
                        </div>
                        <h2 class="text-3xl font-bold">{{ showcases[2].title }}</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">{{ showcases[2].description }}</p>
                        <ul class="mt-6 space-y-3">
                            <li v-for="f in showcases[2].features" :key="f" class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                                <Check class="h-4 w-4 shrink-0 text-amber-600" />
                                {{ f }}
                            </li>
                        </ul>
                    </div>
                    <!-- Right: Inventory table mockup -->
                    <div class="hidden lg:block">
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                            <div class="mb-4 flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Inventory Overview</span>
                                <div class="flex items-center gap-1 rounded-md border border-gray-200 px-3 py-1.5 text-xs text-gray-400 dark:border-gray-700">
                                    <Search class="h-3 w-3" /> Search products...
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="grid grid-cols-4 gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400 px-3">
                                    <span>Product</span><span>SKU</span><span>Stock</span><span>Status</span>
                                </div>
                                <div v-for="(item, i) in [
                                    { name: 'Arabica Coffee 250g', sku: 'COF-001', stock: 142, status: 'In Stock', color: 'green' },
                                    { name: 'Paper Cup Large', sku: 'SUP-015', stock: 23, status: 'Low Stock', color: 'red' },
                                    { name: 'Chocolate Syrup', sku: 'SYR-003', stock: 56, status: 'In Stock', color: 'green' },
                                    { name: 'Milk 1L Fresh', sku: 'DRY-008', stock: 38, status: 'Medium', color: 'yellow' },
                                    { name: 'Croissant Plain', sku: 'BKR-012', stock: 85, status: 'In Stock', color: 'green' },
                                ]" :key="i" class="grid grid-cols-4 gap-2 rounded-lg border border-gray-100 bg-gray-50/50 px-3 py-2.5 text-xs dark:border-gray-800 dark:bg-gray-800/30">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ item.name }}</span>
                                    <span class="text-gray-500">{{ item.sku }}</span>
                                    <span class="font-medium">{{ item.stock }}</span>
                                    <span>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="{
                                                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': item.color === 'green',
                                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': item.color === 'red',
                                                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': item.color === 'yellow',
                                            }"
                                        >{{ item.status }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== 8. COMPLETE FEATURE GRID ==================== -->
        <section class="relative py-20 sm:py-28 overflow-hidden">
            <!-- Subtle gradient mesh background -->
            <div class="feature-grid-bg absolute inset-0"></div>
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center" data-animate>
                    <span class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-4 py-1.5 text-sm font-medium text-teal-700 dark:border-teal-800 dark:bg-teal-900/30 dark:text-teal-300">
                        <Zap class="h-4 w-4" />
                        Complete Platform
                    </span>
                    <h2 class="mt-4 text-3xl font-bold sm:text-4xl">Every Feature Your Business Needs</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-400">
                        A comprehensive suite of 20+ features designed to handle every aspect of your business operations.
                    </p>
                </div>

                <div class="mt-14 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4" data-animate>
                    <div
                        v-for="feature in [
                            { icon: Monitor, title: 'Point of Sale', desc: 'Fast checkout with smart cart management' },
                            { icon: ChefHat, title: 'Kitchen Display', desc: 'Real-time order queue for kitchens' },
                            { icon: ClipboardList, title: 'Inventory Management', desc: 'Track stock across all branches' },
                            { icon: Shuffle, title: 'Stock Transfers', desc: 'Move inventory between branches' },
                            { icon: Truck, title: 'Purchase Orders', desc: 'Draft, send & receive PO workflows' },
                            { icon: BarChart3, title: 'Reports & Analytics', desc: '8 report types with date filtering' },
                            { icon: Building2, title: 'Multi-Branch', desc: 'Centralized control of all locations' },
                            { icon: ShieldCheck, title: 'Role-Based Access', desc: '50+ granular permission controls' },
                            { icon: Clock, title: 'Shift Management', desc: 'Cash reconciliation & shift tracking' },
                            { icon: Heart, title: 'Customer Management', desc: 'Customer database & order history' },
                            { icon: Tag, title: 'Promotions & Promos', desc: 'Discounts, BOGO & promo codes' },
                            { icon: TableProperties, title: 'Table Management', desc: 'Dine-in table tracking & assignment' },
                            { icon: Boxes, title: 'Product Variations', desc: 'Sizes, add-ons & modifiers' },
                            { icon: ScanBarcode, title: 'Barcode Scanning', desc: 'Hardware & camera-based scanning' },
                            { icon: Printer, title: 'Receipt & KOT Print', desc: 'PDF receipts & kitchen order tickets' },
                            { icon: Globe, title: 'Supplier Management', desc: 'Supplier directory & contact info' },
                            { icon: CalendarDays, title: 'Employee Scheduling', desc: 'Shift scheduling & assignments' },
                            { icon: History, title: 'Audit Logs', desc: 'Complete activity trail for changes' },
                            { icon: Fingerprint, title: 'Two-Factor Auth', desc: 'Extra security layer for accounts' },
                            { icon: FileSpreadsheet, title: 'CSV Import/Export', desc: 'Bulk data management made easy' },
                        ]"
                        :key="feature.title"
                        class="feature-grid-card group rounded-xl border border-gray-200/60 p-5 backdrop-blur-sm transition-all duration-200 hover:scale-[1.02] hover:border-teal-300 hover:shadow-lg bg-white/70 dark:border-gray-800/60 dark:bg-gray-900/70 dark:hover:border-teal-700"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-teal-600 transition group-hover:bg-teal-100 dark:bg-teal-900/20 dark:text-teal-400 dark:group-hover:bg-teal-900/40">
                            <component :is="feature.icon" class="h-5 w-5" />
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ feature.title }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ feature.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== 9. ADVANCED CAPABILITIES ==================== -->
        <section class="border-t border-gray-200 bg-gray-50/50 py-20 dark:border-gray-800 dark:bg-gray-900/30">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center" data-animate>
                    <h2 class="text-3xl font-bold sm:text-4xl">Advanced Capabilities</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-400">
                        Enterprise-grade features that give your business a competitive edge.
                    </p>
                </div>

                <div class="mt-14 grid gap-8 md:grid-cols-3" data-animate>
                    <!-- Column 1: Smart Operations -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-8 dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <Zap class="h-6 w-6" />
                        </div>
                        <h3 class="mt-5 text-xl font-bold">Smart Operations</h3>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-start gap-3 text-sm">
                                <ScanBarcode class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">Barcode Scanner Integration</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">Hardware & camera-based scanning</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 text-sm">
                                <Fingerprint class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">PIN-Based Operator Switching</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">Fast user switching at POS</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 text-sm">
                                <Printer class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">Auto KOT Printing</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">Send orders directly to kitchen</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 text-sm">
                                <Receipt class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">Tax-Inclusive/Exclusive Modes</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">Flexible tax configuration</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 2: Business Intelligence -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-8 dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            <TrendingUp class="h-6 w-6" />
                        </div>
                        <h3 class="mt-5 text-xl font-bold">Business Intelligence</h3>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-start gap-3 text-sm">
                                <LineChart class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">Sales Forecasting</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">AI-powered trend predictions</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 text-sm">
                                <Star class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">Operator Performance Rankings</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">Track & compare staff metrics</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 text-sm">
                                <PieChart class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">Branch Comparison Analytics</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">Side-by-side performance insights</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 text-sm">
                                <FileSpreadsheet class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">CSV/PDF Export</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">Export all reports instantly</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 3: Enterprise Security -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-8 dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                            <Lock class="h-6 w-6" />
                        </div>
                        <h3 class="mt-5 text-xl font-bold">Enterprise Security</h3>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-start gap-3 text-sm">
                                <Fingerprint class="mt-0.5 h-4 w-4 shrink-0 text-purple-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">Two-Factor Authentication</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">TOTP-based 2FA for all users</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 text-sm">
                                <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-purple-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">50+ Granular Permissions</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">Fine-grained access control</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 text-sm">
                                <Users class="mt-0.5 h-4 w-4 shrink-0 text-purple-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">Custom Role Creation</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">Build roles to fit your org</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 text-sm">
                                <History class="mt-0.5 h-4 w-4 shrink-0 text-purple-600" />
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">Complete Audit Trail</span>
                                    <p class="mt-0.5 text-gray-500 dark:text-gray-400">Track every action & change</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== 10. HOW IT WORKS ==================== -->
        <section id="how-it-works" class="py-20 sm:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center" data-animate>
                    <h2 class="text-3xl font-bold sm:text-4xl">Get Started in Minutes</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-400">
                        Four simple steps to transform how you run your business.
                    </p>
                </div>

                <div class="relative mt-16" data-animate>
                    <!-- Connection line (desktop only) -->
                    <div class="absolute left-0 right-0 top-10 hidden h-0.5 border-t-2 border-dashed border-gray-300 lg:block dark:border-gray-700" style="margin-left: 12.5%; margin-right: 12.5%;"></div>

                    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        <div v-for="(step, idx) in howItWorks" :key="step.title" class="relative text-center">
                            <div class="relative z-10 mx-auto flex h-20 w-20 items-center justify-center rounded-full border-2 border-teal-600 bg-white text-teal-600 dark:bg-gray-950">
                                <component :is="step.icon" class="h-8 w-8" />
                                <span class="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">{{ idx + 1 }}</span>
                            </div>
                            <h3 class="mt-6 text-lg font-semibold">{{ step.title }}</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ step.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== 9. BUSINESS TYPES ==================== -->
        <section class="border-t border-gray-200 bg-gray-50/50 py-20 dark:border-gray-800 dark:bg-gray-900/30">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center" data-animate>
                    <h2 class="text-3xl font-bold sm:text-4xl">Built for Every Business Type</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-400">
                        Whether you're running a coffee shop or a hardware store, OmniPOS adapts to your needs.
                    </p>
                </div>

                <div class="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5" data-animate>
                    <div
                        v-for="biz in businessTypes"
                        :key="biz.name"
                        class="group flex flex-col items-center rounded-xl border border-gray-200 bg-white p-5 transition hover:-translate-y-1 hover:border-teal-300 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-teal-700"
                    >
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-50 text-teal-600 transition group-hover:bg-teal-100 dark:bg-teal-900/20 dark:text-teal-400 dark:group-hover:bg-teal-900/40">
                            <component :is="biz.icon" class="h-6 w-6" />
                        </div>
                        <h3 class="mt-3 text-sm font-semibold">{{ biz.name }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ biz.count }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== 10. PRICING ==================== -->
        <section id="pricing" class="relative py-20 sm:py-28">
            <div class="absolute inset-0 bg-gradient-to-b from-white via-teal-50/30 to-white dark:from-gray-950 dark:via-teal-950/10 dark:to-gray-950"></div>
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center" data-animate>
                    <h2 class="text-3xl font-bold sm:text-4xl">Simple, Transparent Pricing</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-gray-600 dark:text-gray-400">
                        Start for free, upgrade when you're ready. No hidden fees.
                    </p>
                </div>

                <div class="mt-16 grid gap-8 md:grid-cols-3" data-animate>
                    <div
                        v-for="plan in plans"
                        :key="plan.slug"
                        class="relative flex flex-col rounded-2xl border bg-white p-8 transition hover:shadow-xl dark:bg-gray-900"
                        :class="plan.slug === 'pro'
                            ? 'border-teal-600 shadow-lg shadow-teal-600/10 ring-2 ring-teal-600 dark:border-teal-500 dark:ring-teal-500'
                            : 'border-gray-200 dark:border-gray-800'"
                    >
                        <div v-if="plan.slug === 'pro'" class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-teal-600 px-4 py-1 text-xs font-semibold text-white">
                            Most Popular
                        </div>
                        <h3 class="text-xl font-bold">{{ plan.name }}</h3>
                        <div class="mt-4">
                            <span class="text-4xl font-extrabold">₱{{ Number(plan.price).toLocaleString('en-PH') }}</span>
                            <span v-if="Number(plan.price) > 0" class="text-gray-500">/month</span>
                            <span v-else class="text-gray-500"> forever</span>
                        </div>
                        <ul class="mt-8 flex-1 space-y-3">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-3 text-sm">
                                <Check class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                                {{ feature }}
                            </li>
                        </ul>
                        <Link
                            v-if="canRegister"
                            :href="`/register?plan=${plan.slug}`"
                            class="mt-8 block rounded-lg py-3 text-center text-sm font-semibold transition"
                            :class="plan.slug === 'pro'
                                ? 'bg-teal-600 text-white shadow-md hover:bg-teal-700'
                                : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800'"
                        >
                            {{ Number(plan.price) === 0 ? 'Get Started Free' : 'Start Free Trial' }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== 11. ABOUT / FINAL CTA ==================== -->
        <section id="about" class="py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" data-animate>
                <div class="rounded-3xl bg-gradient-to-r from-teal-600 to-cyan-600 px-8 py-16 text-center text-white shadow-2xl sm:px-16">
                    <h2 class="text-3xl font-bold sm:text-4xl">Ready to Transform Your Business?</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-teal-100">
                        OmniPOS is designed specifically for Filipino businesses. Whether you're running a single store
                        or managing multiple branches, our platform helps you streamline operations and grow.
                    </p>

                    <div class="mt-10 flex flex-wrap justify-center gap-6">
                        <div class="flex items-center gap-2 text-sm font-medium">
                            <Check class="h-5 w-5" />
                            Free plan forever
                        </div>
                        <div class="flex items-center gap-2 text-sm font-medium">
                            <Check class="h-5 w-5" />
                            No credit card required
                        </div>
                        <div class="flex items-center gap-2 text-sm font-medium">
                            <Check class="h-5 w-5" />
                            Setup in 5 minutes
                        </div>
                        <div class="flex items-center gap-2 text-sm font-medium">
                            <Check class="h-5 w-5" />
                            Cancel anytime
                        </div>
                    </div>

                    <div class="mt-10">
                        <Link
                            v-if="canRegister"
                            href="/register"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-8 py-4 text-base font-bold text-teal-700 shadow-lg transition hover:bg-teal-50 hover:shadow-xl"
                        >
                            Get Started for Free
                            <ArrowRight class="h-5 w-5" />
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== 12. FOOTER ==================== -->
        <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50" role="contentinfo">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-5">
                    <!-- Brand -->
                    <div>
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <span class="font-bold">OmniPOS</span>
                        </div>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            The all-in-one point-of-sale system built for Filipino businesses. Manage, sell, and grow.
                        </p>
                    </div>

                    <!-- Product -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Product</h4>
                        <ul class="mt-4 space-y-3">
                            <li><button @click="scrollTo('features')" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Features</button></li>
                            <li><button @click="scrollTo('pricing')" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Pricing</button></li>
                            <li><button @click="scrollTo('how-it-works')" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">How It Works</button></li>
                        </ul>
                    </div>

                    <!-- Company -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Company</h4>
                        <ul class="mt-4 space-y-3">
                            <li><Link href="/about" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">About</Link></li>
                        </ul>
                    </div>

                    <!-- Legal -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Legal</h4>
                        <ul class="mt-4 space-y-3">
                            <li><Link href="/privacy" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Privacy Policy</Link></li>
                            <li><Link href="/terms" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Terms of Use</Link></li>
                        </ul>
                    </div>

                    <!-- Get Started -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Get Started</h4>
                        <ul class="mt-4 space-y-3">
                            <li v-if="canRegister"><Link href="/register" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Create Account</Link></li>
                            <li><Link href="/login" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Sign In</Link></li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom bar -->
                <div class="mt-12 border-t border-gray-200 pt-8 text-center dark:border-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        &copy; {{ new Date().getFullYear() }} OmniPOS. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>

    <!-- ==================== COOKIE CONSENT MODAL ==================== -->
    <Dialog :open="showCookieModal" @update:open="(v: boolean) => { if (!v) declineCookies() }">
        <DialogContent :show-close-button="false" class="sm:max-w-md">
            <div class="h-1.5 absolute top-0 left-0 right-0 rounded-t-lg bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600"></div>
            <DialogHeader class="pt-2">
                <DialogTitle class="flex items-center gap-2 text-xl">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/50">
                        <Cookie class="h-4.5 w-4.5 text-amber-600 dark:text-amber-400" />
                    </div>
                    Cookie Notice
                </DialogTitle>
            </DialogHeader>
            <DialogDescription class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                <p>
                    We use <span class="font-medium text-gray-800 dark:text-gray-200">essential cookies</span>
                    to keep the app running smoothly — things like your session, preferences, and security tokens.
                    These are necessary for the site to function properly.
                </p>
            </DialogDescription>
            <DialogFooter class="flex gap-3 sm:gap-3">
                <Button variant="outline" @click="declineCookies" class="flex-1">
                    Decline
                </Button>
                <Button @click="acceptCookies" class="flex-1 bg-teal-600 hover:bg-teal-700">
                    Accept
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
/* Hero gradient background */
.hero-bg {
    background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 50%, #f0f9ff 100%);
}

:root.dark .hero-bg,
.dark .hero-bg {
    background: linear-gradient(135deg, rgba(13, 148, 136, 0.08) 0%, rgba(6, 182, 212, 0.05) 50%, rgba(14, 165, 233, 0.03) 100%);
}

/* POS mockup float animation */
.pos-mockup {
    animation: float 6s ease-in-out infinite;
    position: relative;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-12px); }
}

/* Floating badges bounce */
.badge-float-1 {
    animation: bounce-slow 3s ease-in-out infinite;
}

.badge-float-2 {
    animation: bounce-slow 3s ease-in-out infinite 1.5s;
}

@keyframes bounce-slow {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

/* Scroll reveal animation */
[data-animate] {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}

[data-animate].is-visible {
    opacity: 1;
    transform: translateY(0);
}

/* Feature grid gradient mesh background */
.feature-grid-bg {
    background:
        radial-gradient(ellipse at 20% 50%, rgba(20, 184, 166, 0.06) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 20%, rgba(6, 182, 212, 0.06) 0%, transparent 50%),
        radial-gradient(ellipse at 60% 80%, rgba(99, 102, 241, 0.04) 0%, transparent 50%);
}

:root.dark .feature-grid-bg,
.dark .feature-grid-bg {
    background:
        radial-gradient(ellipse at 20% 50%, rgba(20, 184, 166, 0.08) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 20%, rgba(6, 182, 212, 0.06) 0%, transparent 50%),
        radial-gradient(ellipse at 60% 80%, rgba(99, 102, 241, 0.05) 0%, transparent 50%);
}

/* Reduce motion for accessibility */
@media (prefers-reduced-motion: reduce) {
    .pos-mockup,
    .badge-float-1,
    .badge-float-2 {
        animation: none;
    }
    [data-animate] {
        opacity: 1;
        transform: none;
        transition: none;
    }
    .feature-grid-card {
        transition: none;
    }
}
</style>
