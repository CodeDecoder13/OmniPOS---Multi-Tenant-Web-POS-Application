<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowRight, Boxes, Check, ChevronDown, UserPlus, Settings, Play, Rocket, BarChart3,
    Coffee, Store, Shirt, Pill, Utensils, Wrench, Cake, Wine, ShoppingCart, LayoutGrid,
} from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';

withDefaults(defineProps<{ canRegister?: boolean }>(), { canRegister: true });

const steps = [
    {
        icon: UserPlus,
        title: 'Create your account',
        time: '~1 minute',
        body: 'Sign up with your email or Google. A short guided form asks for your name, your store name, your business type, and your plan. No credit card needed for the Free plan.',
        bullets: [
            'Pick your business type, such as cafe, grocery, pharmacy, or bakery',
            'Your workspace gets its own private address',
            'Default product categories for your business type are created for you',
        ],
    },
    {
        icon: Settings,
        title: 'Set up your first branch and PIN',
        time: '~3 minutes',
        body: 'A two-step setup wizard walks you through your first branch (name, address, contact) and a personal POS PIN, which is how operators sign in to the register.',
        bullets: [
            'Inventory records are created automatically for the new branch',
            'You are assigned to your branch from day one',
            'Add more branches later whenever you grow',
        ],
    },
    {
        icon: Boxes,
        title: 'Add products and your team',
        time: '~5 minutes',
        body: 'Add products one by one with photos, variations, and add-ons, or import a CSV in bulk. Invite staff and give each person a role that fits their job.',
        bullets: [
            'Barcodes, SKUs, cost price, and sell price in one form',
            'Choose which branches carry each product',
            'Custom roles built from 50+ permissions, assigned per person',
        ],
    },
    {
        icon: Play,
        title: 'Open a shift and start selling',
        time: 'Day one',
        body: 'Open your shift with a starting cash count, then ring up sales. Accept cash, cards, GCash, Maya, or a split of several. Print or send the receipt.',
        bullets: [
            'Hold an order and come back to it later',
            'Restaurants send orders straight to the kitchen display',
            'Refund a full order or a single item with a record',
        ],
    },
    {
        icon: BarChart3,
        title: 'Watch the business from anywhere',
        time: 'Every day',
        body: 'Close the shift and see whether the cash matches. An end-of-day summary lands in your inbox, and your dashboard shows how this month compares with last month.',
        bullets: [
            'Low-stock alerts in the app and by email',
            'Best sellers, payment mix, and operator performance',
            'Compare branches side by side',
        ],
    },
    {
        icon: Rocket,
        title: 'Grow without changing tools',
        time: 'When you are ready',
        body: 'Add branches, create purchase orders for your suppliers, and move stock between locations. Upgrade your plan when you hit a limit, and keep all your data.',
        bullets: [
            'Receiving a purchase order updates inventory automatically',
            'Inter-branch stock transfers with a status trail',
            'Run promotions and promo codes across the business',
        ],
    },
];

const day = [
    { time: 'Opening', text: 'Cashier signs in with a PIN and opens the shift with a cash count.' },
    { time: 'Lunch rush', text: 'Orders flow from the register to the kitchen display. Tables, notes, and split payments are handled in the same screen.' },
    { time: 'Mid-afternoon', text: 'A low-stock alert fires for oat milk. The manager raises a purchase order in two clicks.' },
    { time: 'Closing', text: 'The shift closes, the variance is shown, and the owner gets the day’s summary by email.' },
];

const types = [
    { icon: Coffee, name: 'Cafe' }, { icon: Utensils, name: 'Restaurant' }, { icon: Wine, name: 'Bar & Pub' },
    { icon: Cake, name: 'Bakery' }, { icon: ShoppingCart, name: 'Grocery' }, { icon: Store, name: 'Retail' },
    { icon: Shirt, name: 'Clothing' }, { icon: Pill, name: 'Pharmacy' }, { icon: Wrench, name: 'Hardware' },
    { icon: LayoutGrid, name: 'Other' },
];

const faqs = [
    { q: 'Do I need special hardware?', a: 'No. OmniPOS runs in a browser on a laptop, tablet, or phone. A barcode scanner, receipt printer, or kitchen printer are optional extras.' },
    { q: 'Do I need an internet connection?', a: 'Yes. OmniPOS is cloud-based, which keeps every device and branch in sync in real time and backs your data up automatically.' },
    { q: 'Can I bring my existing products?', a: 'Yes. Import them in bulk with a CSV file, then adjust prices and stock in the app.' },
    { q: 'What if I outgrow the Free plan?', a: 'Upgrade at any time. Your products, customers, and history stay exactly where they are.' },
    { q: 'Can I change my business type later?', a: 'Yes, the business owner can change it in Settings. It resets your products and categories to the new type’s defaults, so the app asks you to confirm first.' },
];

const open = ref<number | null>(null);
</script>

<template>
    <Head title="How It Works — Get Started with OmniPOS in Minutes">
        <meta name="description" content="From sign-up to your first sale in minutes. See how OmniPOS gets your shop, cafe, or restaurant running: account, branch, products, team, and POS." />
        <link rel="canonical" href="https://omnipos.shop/how-it-works" />
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <PublicLayout :can-register="canRegister">
        <section class="relative overflow-hidden">
            <div class="hero-bg absolute inset-0"></div>
            <div class="relative mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-24 lg:px-8" data-animate>
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                    From sign-up to first sale in
                    <span class="bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent dark:from-teal-400 dark:to-cyan-400">under 15 minutes</span>
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg text-gray-600 dark:text-gray-400">
                    No installers, no consultants, no new hardware. Here is exactly what happens from the moment you create your account.
                </p>
                <div class="mt-8">
                    <Link v-if="canRegister" href="/register" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-teal-700">
                        Create Free Account <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- Steps -->
        <section class="py-16 sm:py-24">
            <ol class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6">
                <li
                    v-for="(s, i) in steps"
                    :key="s.title"
                    class="flex gap-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900"
                    data-animate
                >
                    <div class="relative shrink-0">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-teal-600 text-teal-600">
                            <component :is="s.icon" class="h-6 w-6" />
                        </div>
                        <span class="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">{{ i + 1 }}</span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold">{{ s.title }}</h2>
                            <span class="rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">{{ s.time }}</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ s.body }}</p>
                        <ul class="mt-4 space-y-2">
                            <li v-for="b in s.bullets" :key="b" class="flex items-start gap-2 text-sm">
                                <Check class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
                                {{ b }}
                            </li>
                        </ul>
                    </div>
                </li>
            </ol>
        </section>

        <!-- A day -->
        <section class="border-y border-gray-200 bg-gray-50/60 py-16 sm:py-20 dark:border-gray-800 dark:bg-gray-900/30">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <h2 class="text-center text-3xl font-bold" data-animate>A day with OmniPOS</h2>
                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-animate>
                    <div v-for="d in day" :key="d.time" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-sm font-bold text-teal-600">{{ d.time }}</p>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ d.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Business types -->
        <section class="py-16 sm:py-20">
            <div class="mx-auto max-w-5xl px-4 text-center sm:px-6" data-animate>
                <h2 class="text-3xl font-bold">Starts ready for your kind of business</h2>
                <p class="mx-auto mt-3 max-w-2xl text-gray-600 dark:text-gray-400">
                    Choose your business type at sign-up and your product categories are set up for you, so you are not starting from a blank page.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <span v-for="t in types" :key="t.name" class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900">
                        <component :is="t.icon" class="h-4 w-4 text-teal-600" />{{ t.name }}
                    </span>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="border-t border-gray-200 bg-gray-50/50 py-16 sm:py-20 dark:border-gray-800 dark:bg-gray-900/30">
            <div class="mx-auto max-w-3xl px-4 sm:px-6">
                <h2 class="text-center text-3xl font-bold" data-animate>Common questions</h2>
                <div class="mt-10 space-y-3" data-animate>
                    <div v-for="(f, i) in faqs" :key="f.q" class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                        <button class="flex w-full items-center justify-between px-6 py-4 text-left" :aria-expanded="open === i" @click="open = open === i ? null : i">
                            <span class="text-sm font-semibold">{{ f.q }}</span>
                            <ChevronDown class="h-5 w-5 shrink-0 text-gray-400 transition-transform" :class="open === i ? 'rotate-180' : ''" />
                        </button>
                        <p v-show="open === i" class="border-t border-gray-100 px-6 py-4 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400">{{ f.a }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-16 sm:py-20">
            <div class="mx-auto max-w-3xl px-4 text-center" data-animate>
                <h2 class="text-3xl font-bold">Ready to ring up your first sale?</h2>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <Link v-if="canRegister" href="/register" class="rounded-lg bg-teal-600 px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-teal-700">Start Free</Link>
                    <Link href="/pricing" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Compare Plans</Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
