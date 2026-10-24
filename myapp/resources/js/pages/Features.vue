<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight, Check, Monitor, ClipboardList, BarChart3, Building2, ShieldCheck, Heart,
    ChefHat, Tag, Truck, Clock, Sparkles, Boxes, ScanBarcode, Printer, History, Lock, Languages,
} from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';

withDefaults(defineProps<{ canRegister?: boolean }>(), { canRegister: true });

const highlights = [
    { value: '50+', label: 'Permission controls' },
    { value: '8', label: 'Report types' },
    { value: '6', label: 'Stock adjustment types' },
    { value: '3', label: 'Languages (EN, FIL, JA)' },
];

const modules = [
    {
        icon: Monitor,
        title: 'Point of Sale & Checkout',
        pitch: 'Ring up a sale in a few taps, even during the lunch rush. Everything your cashier needs is on one screen.',
        items: [
            'Category browsing, search, and barcode scanning (hardware or camera)',
            'Cash, card, GCash, Maya, bank transfer, and split payments',
            'Hold and recall orders, per-item notes, dine-in, takeout, and delivery',
            'Full and partial refunds with a complete record',
            'Receipts to print, email, or share as a link',
            'PIN-based operator switching so every sale has a name on it',
        ],
        outcome: 'Shorter queues and no more "who rang this up?"',
    },
    {
        icon: ClipboardList,
        title: 'Inventory Control',
        pitch: 'Know exactly what you have in every branch, and get warned before you run out.',
        items: [
            'Per-branch stock levels, created automatically when you add a branch',
            'Low-stock thresholds with in-app and email alerts',
            'Auto-reorder when stock hits your threshold',
            'Six adjustment types (restock, damage, return, and more), each logged',
            'Full audit trail: who changed what, and when',
            'Bulk CSV import and export for products',
        ],
        outcome: 'Fewer stock-outs, less shrinkage, and no end-of-month guessing.',
    },
    {
        icon: Truck,
        title: 'Supply Chain',
        pitch: 'From supplier to shelf, in the same system you sell with.',
        items: [
            'Supplier directory with contact info and per-product cost',
            'Purchase orders: draft, sent, received',
            'Receiving updates your inventory automatically',
            'Inter-branch stock transfers with a clear status trail',
        ],
        outcome: 'One place for ordering, receiving, and moving stock.',
    },
    {
        icon: BarChart3,
        title: 'Reports & Analytics',
        pitch: 'Turn every transaction into something you can act on.',
        items: [
            'Sales trends and revenue analytics with custom date ranges',
            'Best-selling and slow-moving product rankings',
            'Payment method breakdown and operator performance',
            'Branch-versus-branch comparison',
            'End-of-day summary emailed to you automatically',
            'Month-over-month AI Insights summary on your dashboard',
            'Export reports to CSV or PDF',
        ],
        outcome: 'Check your business from your phone without calling the store.',
    },
    {
        icon: Building2,
        title: 'Multi-Branch Management',
        pitch: 'Run one store or ten from a single login.',
        items: [
            'Switch branches instantly from the dashboard',
            'Each user is assigned to a branch',
            'Restrict products to specific branches, or keep them available everywhere',
            'Per-branch inventory, shifts, and orders',
            'Per-branch feature toggles, such as turning inventory tracking or POS on or off',
        ],
        outcome: 'Growth that does not mean more spreadsheets.',
    },
    {
        icon: Clock,
        title: 'Shifts & Cash Reconciliation',
        pitch: 'Close every day knowing the drawer matches the books.',
        items: [
            'Open and close shifts with a cash count',
            'Variance tracking between expected and counted cash',
            'Stale shifts closed automatically',
            'Employee shift scheduling',
            'Per-operator performance metrics',
        ],
        outcome: 'Cash discrepancies are caught the same day, not the same month.',
    },
    {
        icon: ChefHat,
        title: 'Kitchen Display & Table Management',
        pitch: 'Made for restaurants, cafes, bars, and bakeries.',
        items: [
            'Live kitchen order queue: new, preparing, ready, served',
            'KOT printing to kitchen printers and sound alerts for new orders',
            'Table tracking and assignment for dine-in',
            'Product variations and add-ons (sizes, extras, modifiers)',
        ],
        outcome: 'Fewer wrong orders and a calmer kitchen.',
    },
    {
        icon: Tag,
        title: 'Promotions & Discounts',
        pitch: 'Run campaigns without manual price edits.',
        items: [
            'Percentage, fixed-amount, and buy-X-get-Y promotions',
            'Promo codes with minimum order and usage limits',
            'Schedule campaigns by date range',
            'Flexible discounts applied right at checkout',
        ],
        outcome: 'Promos that start and stop on their own.',
    },
    {
        icon: Heart,
        title: 'Customer Management',
        pitch: 'Know who your regulars are and what they buy.',
        items: [
            'Customer database with notes and contact details',
            'Orders, total spend, and average order per customer',
            'Quick customer lookup at checkout',
            'Digital receipts by email or shareable link',
        ],
        outcome: 'Turn walk-ins into regulars.',
    },
];

const trust = [
    { icon: ShieldCheck, title: 'Role-based access', body: 'Build custom roles from 50+ permissions, so a cashier sees the POS and nothing else.' },
    { icon: Lock, title: 'Two-factor authentication', body: 'TOTP-based 2FA protects owner and staff accounts.' },
    { icon: History, title: 'Audit trail', body: 'Product, stock, and settings changes are logged with who and when.' },
    { icon: Boxes, title: 'Isolated tenant data', body: 'Every business is a separate tenant. Your data is never mixed with anyone else’s.' },
    { icon: Printer, title: 'Receipts you control', body: 'Custom header and footer, and full branding on Enterprise.' },
    { icon: Languages, title: 'Speaks your language', body: 'English, Filipino, and Japanese, and a currency that matches your market.' },
];
</script>

<template>
    <Head title="Features — OmniPOS POS, Inventory, Reports & Multi-Branch">
        <meta name="description" content="Explore every OmniPOS feature: fast POS checkout, inventory control, purchase orders, multi-branch management, kitchen display, promotions, reports, and role-based security." />
        <link rel="canonical" href="https://omnipos.shop/features" />
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <PublicLayout :can-register="canRegister">
        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="hero-bg absolute inset-0"></div>
            <div class="relative mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:px-8" data-animate>
                <span class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-4 py-1.5 text-sm font-medium text-teal-700 dark:border-teal-800 dark:bg-teal-900/30 dark:text-teal-300">
                    <Sparkles class="h-4 w-4" />
                    One system, every part of your business
                </span>
                <h1 class="mt-6 text-4xl font-extrabold tracking-tight sm:text-5xl">
                    Everything you need to
                    <span class="bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent dark:from-teal-400 dark:to-cyan-400">sell, stock, and scale</span>
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg text-gray-600 dark:text-gray-400">
                    Stop paying for a POS, an inventory app, and a reporting tool that don’t talk to each other. OmniPOS puts them in one place, built around how Philippine shops actually work.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <Link v-if="canRegister" href="/register" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-teal-700">
                        Start Free <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link href="/pricing" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        See Pricing
                    </Link>
                </div>
            </div>
        </section>

        <!-- Numbers -->
        <section class="border-y border-gray-200 bg-gray-50/60 dark:border-gray-800 dark:bg-gray-900/30">
            <div class="mx-auto grid max-w-5xl grid-cols-2 gap-6 px-4 py-10 text-center sm:px-6 md:grid-cols-4" data-animate>
                <div v-for="h in highlights" :key="h.label">
                    <p class="text-3xl font-extrabold text-teal-600 dark:text-teal-400">{{ h.value }}</p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ h.label }}</p>
                </div>
            </div>
        </section>

        <!-- Modules -->
        <section class="py-16 sm:py-24">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <article
                    v-for="m in modules"
                    :key="m.title"
                    class="grid gap-6 rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 md:grid-cols-5 dark:border-gray-800 dark:bg-gray-900"
                    data-animate
                >
                    <div class="md:col-span-2">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <component :is="m.icon" class="h-6 w-6" />
                        </div>
                        <h2 class="mt-4 text-xl font-bold">{{ m.title }}</h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ m.pitch }}</p>
                        <p class="mt-4 rounded-lg bg-teal-50 px-3 py-2 text-sm font-medium text-teal-800 dark:bg-teal-900/20 dark:text-teal-300">
                            {{ m.outcome }}
                        </p>
                    </div>
                    <ul class="space-y-3 md:col-span-3">
                        <li v-for="item in m.items" :key="item" class="flex items-start gap-3 text-sm">
                            <Check class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </article>
            </div>
        </section>

        <!-- Trust -->
        <section class="border-t border-gray-200 bg-gray-50/50 py-16 sm:py-24 dark:border-gray-800 dark:bg-gray-900/30">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="text-center" data-animate>
                    <h2 class="text-3xl font-bold">Built to be trusted with your business</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-gray-600 dark:text-gray-400">Security and accountability are part of the product, not an add-on.</p>
                </div>
                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-animate>
                    <div v-for="t in trust" :key="t.title" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                        <component :is="t.icon" class="h-6 w-6 text-teal-600" />
                        <h3 class="mt-3 font-semibold">{{ t.title }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ t.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-16 sm:py-20">
            <div class="mx-auto max-w-3xl px-4 text-center" data-animate>
                <ScanBarcode class="mx-auto h-10 w-10 text-teal-600" />
                <h2 class="mt-4 text-3xl font-bold">See it with your own products</h2>
                <p class="mt-3 text-gray-600 dark:text-gray-400">The Free plan has no time limit and no credit card. Set up in minutes, upgrade only when you need more.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <Link v-if="canRegister" href="/register" class="rounded-lg bg-teal-600 px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-teal-700">Create Free Account</Link>
                    <Link href="/how-it-works" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">How It Works</Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
