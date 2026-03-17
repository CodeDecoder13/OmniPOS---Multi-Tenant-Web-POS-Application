<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import {
    Menu, X, Check, ArrowRight,
    Monitor, DollarSign, Zap, Shield, Clock, Users,
    BarChart3, Package, CreditCard, Building2, Smartphone, CloudOff,
} from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    { canRegister: true },
);

const mobileMenuOpen = ref(false);
const scrolled = ref(false);

function handleScroll() {
    scrolled.value = window.scrollY > 10;
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) entry.target.classList.add('is-visible');
            });
        },
        { threshold: 0.1 },
    );

    document.querySelectorAll('[data-animate]').forEach((el) => observer.observe(el));

    onUnmounted(() => {
        window.removeEventListener('scroll', handleScroll);
        observer.disconnect();
    });
});
</script>

<template>
    <Head title="About OmniPOS - Simple, Cost-Saving POS">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100">
        <!-- ==================== NAVBAR ==================== -->
        <nav
            class="sticky top-0 z-50 border-b backdrop-blur-lg transition-shadow duration-300"
            :class="scrolled
                ? 'border-gray-200 bg-white/80 shadow-lg dark:border-gray-800 dark:bg-gray-950/80'
                : 'border-transparent bg-white/60 dark:bg-gray-950/60'"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-600">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold">OmniPOS</span>
                </Link>

                <div class="hidden items-center gap-8 md:flex">
                    <Link href="/#features" class="text-sm text-gray-600 transition hover:text-teal-600 dark:text-gray-400 dark:hover:text-teal-400">Features</Link>
                    <Link href="/#how-it-works" class="text-sm text-gray-600 transition hover:text-teal-600 dark:text-gray-400 dark:hover:text-teal-400">How It Works</Link>
                    <Link href="/#pricing" class="text-sm text-gray-600 transition hover:text-teal-600 dark:text-gray-400 dark:hover:text-teal-400">Pricing</Link>
                    <span class="text-sm font-medium text-teal-600 dark:text-teal-400">About</span>
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

                <button class="md:hidden" @click="mobileMenuOpen = !mobileMenuOpen">
                    <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
            </div>

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
                        <Link href="/#features" class="text-left text-sm text-gray-600 hover:text-teal-600 dark:text-gray-400">Features</Link>
                        <Link href="/#how-it-works" class="text-left text-sm text-gray-600 hover:text-teal-600 dark:text-gray-400">How It Works</Link>
                        <Link href="/#pricing" class="text-left text-sm text-gray-600 hover:text-teal-600 dark:text-gray-400">Pricing</Link>
                        <span class="text-left text-sm font-medium text-teal-600">About</span>
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

        <!-- ==================== HERO ==================== -->
        <section class="relative overflow-hidden">
            <div class="hero-bg absolute inset-0"></div>
            <div class="absolute inset-0 bg-[linear-gradient(rgba(0,0,0,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(0,0,0,0.03)_1px,transparent_1px)] bg-[size:4rem_4rem] dark:bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)]"></div>

            <div class="relative mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 sm:py-28 lg:px-8 lg:py-36" data-animate>
                <span class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-4 py-1.5 text-sm font-medium text-teal-700 dark:border-teal-800 dark:bg-teal-900/30 dark:text-teal-300">
                    <Zap class="h-4 w-4" />
                    Simple. Affordable. All-in-One.
                </span>

                <h1 class="mt-6 text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                    Stop Overpaying for
                    <span class="bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent dark:from-teal-400 dark:to-cyan-400">
                        Business Software
                    </span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600 dark:text-gray-400">
                    OmniPOS replaces multiple expensive tools with one simple web app. Manage your POS, inventory, staff,
                    branches, and reports — all from a single platform, at a fraction of the cost.
                </p>
            </div>
        </section>

        <!-- ==================== THE PROBLEM ==================== -->
        <section class="border-t border-gray-200 py-20 dark:border-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center" data-animate>
                    <h2 class="text-3xl font-bold sm:text-4xl">The Problem with Traditional POS</h2>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        Most businesses juggle multiple tools and subscriptions just to stay operational. It's expensive, complicated, and wastes time.
                    </p>
                </div>

                <div class="mt-14 grid gap-6 sm:grid-cols-3" data-animate>
                    <div class="rounded-xl border border-red-200 bg-red-50/50 p-6 dark:border-red-900/50 dark:bg-red-950/20">
                        <DollarSign class="h-8 w-8 text-red-500" />
                        <h3 class="mt-4 text-lg font-semibold text-red-700 dark:text-red-400">Expensive Subscriptions</h3>
                        <p class="mt-2 text-sm text-red-600/80 dark:text-red-400/70">Separate fees for POS hardware, inventory software, reporting tools, and payroll — costs add up fast.</p>
                    </div>
                    <div class="rounded-xl border border-red-200 bg-red-50/50 p-6 dark:border-red-900/50 dark:bg-red-950/20">
                        <Clock class="h-8 w-8 text-red-500" />
                        <h3 class="mt-4 text-lg font-semibold text-red-700 dark:text-red-400">Wasted Time</h3>
                        <p class="mt-2 text-sm text-red-600/80 dark:text-red-400/70">Switching between apps, manually syncing data, and training staff on multiple systems eats into your day.</p>
                    </div>
                    <div class="rounded-xl border border-red-200 bg-red-50/50 p-6 dark:border-red-900/50 dark:bg-red-950/20">
                        <CloudOff class="h-8 w-8 text-red-500" />
                        <h3 class="mt-4 text-lg font-semibold text-red-700 dark:text-red-400">Data Silos</h3>
                        <p class="mt-2 text-sm text-red-600/80 dark:text-red-400/70">Sales in one tool, inventory in another, reports somewhere else — you never get the full picture of your business.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== THE SOLUTION ==================== -->
        <section class="bg-gray-50/50 py-20 dark:bg-gray-900/30">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center" data-animate>
                    <h2 class="text-3xl font-bold sm:text-4xl">One App. Everything You Need.</h2>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        OmniPOS consolidates all your business operations into a single, easy-to-use web application.
                        No extra hardware. No separate subscriptions. Just one tool that does it all.
                    </p>
                </div>

                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-animate>
                    <div class="rounded-xl border border-gray-200 bg-white p-6 transition hover:border-teal-300 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-teal-700">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <Monitor class="h-5 w-5" />
                        </div>
                        <h3 class="mt-4 font-semibold">Point of Sale</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Fast checkout, multiple payment methods, order types, discounts, and receipt printing — all built in.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-6 transition hover:border-teal-300 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-teal-700">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <Package class="h-5 w-5" />
                        </div>
                        <h3 class="mt-4 font-semibold">Inventory Management</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Track stock per branch, get low-stock alerts, log adjustments, and maintain a full audit trail.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-6 transition hover:border-teal-300 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-teal-700">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <BarChart3 class="h-5 w-5" />
                        </div>
                        <h3 class="mt-4 font-semibold">Reports & Analytics</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Sales trends, product performance, operator tracking, and branch comparisons — all in one dashboard.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-6 transition hover:border-teal-300 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-teal-700">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <Users class="h-5 w-5" />
                        </div>
                        <h3 class="mt-4 font-semibold">Staff & Roles</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">27 granular permissions, custom roles, PIN-based POS login, and 2FA — control who can do what.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-6 transition hover:border-teal-300 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-teal-700">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <Building2 class="h-5 w-5" />
                        </div>
                        <h3 class="mt-4 font-semibold">Multi-Branch</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Manage all locations from one account. Separate data per branch, unified oversight for you.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-6 transition hover:border-teal-300 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-teal-700">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <CreditCard class="h-5 w-5" />
                        </div>
                        <h3 class="mt-4 font-semibold">Shift & Payments</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Shift management with cash reconciliation plus 5 payment methods — Cash, Card, GCash, Maya, and Bank Transfer.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== COST COMPARISON ==================== -->
        <section class="py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center" data-animate>
                    <h2 class="text-3xl font-bold sm:text-4xl">Save Thousands Every Year</h2>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        See how OmniPOS compares to paying for separate tools.
                    </p>
                </div>

                <div class="mt-14 grid items-start gap-8 lg:grid-cols-2" data-animate>
                    <!-- Traditional way -->
                    <div class="rounded-2xl border border-red-200 bg-white p-8 dark:border-red-900/50 dark:bg-gray-900">
                        <h3 class="text-lg font-bold text-red-600">Without OmniPOS</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Typical monthly costs with separate tools</p>
                        <div class="mt-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                <span class="text-sm text-gray-700 dark:text-gray-300">POS Software</span>
                                <span class="text-sm font-semibold text-red-600">₱2,500/mo</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Inventory System</span>
                                <span class="text-sm font-semibold text-red-600">₱1,500/mo</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Reporting Tool</span>
                                <span class="text-sm font-semibold text-red-600">₱1,000/mo</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Staff Management</span>
                                <span class="text-sm font-semibold text-red-600">₱800/mo</span>
                            </div>
                            <div class="flex items-center justify-between pt-2">
                                <span class="font-bold text-gray-900 dark:text-gray-100">Total</span>
                                <span class="text-xl font-extrabold text-red-600">₱5,800/mo</span>
                            </div>
                        </div>
                    </div>

                    <!-- OmniPOS way -->
                    <div class="rounded-2xl border-2 border-teal-600 bg-white p-8 shadow-lg shadow-teal-600/10 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-teal-600">With OmniPOS</h3>
                            <span class="rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">All-in-One</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Everything included in one plan</p>
                        <div class="mt-6 space-y-4">
                            <div class="flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                                <Check class="h-4 w-4 shrink-0 text-teal-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Full POS System</span>
                            </div>
                            <div class="flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                                <Check class="h-4 w-4 shrink-0 text-teal-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Inventory Management</span>
                            </div>
                            <div class="flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                                <Check class="h-4 w-4 shrink-0 text-teal-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">6 Report Categories</span>
                            </div>
                            <div class="flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                                <Check class="h-4 w-4 shrink-0 text-teal-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Staff, Roles & Permissions</span>
                            </div>
                            <div class="flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                                <Check class="h-4 w-4 shrink-0 text-teal-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Multi-Branch + Shifts + Customers</span>
                            </div>
                            <div class="flex items-center justify-between pt-2">
                                <span class="font-bold text-gray-900 dark:text-gray-100">Starting at</span>
                                <div class="text-right">
                                    <span class="text-xl font-extrabold text-teal-600">₱0/mo</span>
                                    <span class="ml-1 text-sm text-gray-500">free forever</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== WHY WEB APP ==================== -->
        <section class="border-t border-gray-200 bg-gray-50/50 py-20 dark:border-gray-800 dark:bg-gray-900/30">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center" data-animate>
                    <h2 class="text-3xl font-bold sm:text-4xl">Why a Web App?</h2>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        No installations. No expensive hardware. Just open your browser and run your business.
                    </p>
                </div>

                <div class="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-4" data-animate>
                    <div class="text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <Smartphone class="h-7 w-7" />
                        </div>
                        <h3 class="mt-4 font-semibold">Any Device</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Works on laptops, tablets, and phones. Use whatever hardware you already own.</p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <Zap class="h-7 w-7" />
                        </div>
                        <h3 class="mt-4 font-semibold">Instant Updates</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Always on the latest version. No manual updates, no downtime, no IT needed.</p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <Shield class="h-7 w-7" />
                        </div>
                        <h3 class="mt-4 font-semibold">Secure by Default</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Your data is isolated per tenant with enterprise-grade security, 2FA, and encrypted connections.</p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                            <DollarSign class="h-7 w-7" />
                        </div>
                        <h3 class="mt-4 font-semibold">Zero Hardware Cost</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">No POS terminals to buy, no servers to maintain. Just a browser and an internet connection.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== LESS HASSLE ==================== -->
        <section class="py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-2" data-animate>
                    <div>
                        <h2 class="text-3xl font-bold sm:text-4xl">Less Hassle, More Selling</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">
                            We built OmniPOS to eliminate the busywork so you can focus on what matters — serving customers and growing your business.
                        </p>

                        <div class="mt-8 space-y-5">
                            <div class="flex gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                                    <Zap class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="font-semibold">Setup in 5 Minutes</h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Create an account, add your products, and start selling. No technical knowledge required.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                                    <Monitor class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="font-semibold">One Dashboard for Everything</h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Sales, inventory, staff, customers, reports — no more jumping between different apps.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                                    <Shield class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="font-semibold">Your Staff Can't Break It</h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">27 permissions let you control exactly what each employee can see and do. PIN-based POS login keeps it simple.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                                    <Building2 class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="font-semibold">Scale Without Complexity</h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Opening a second branch? Just add it. Same account, same interface, separate data — zero extra setup.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Simple illustrative card -->
                    <div class="hidden lg:block">
                        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                            <div class="mb-6 text-center">
                                <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">Your typical day with OmniPOS</div>
                            </div>
                            <div class="space-y-4">
                                <div class="flex items-center gap-4 rounded-xl bg-teal-50 p-4 dark:bg-teal-900/20">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">1</div>
                                    <div>
                                        <div class="text-sm font-semibold">Open browser, start shift</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">30 seconds</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 rounded-xl bg-teal-50 p-4 dark:bg-teal-900/20">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">2</div>
                                    <div>
                                        <div class="text-sm font-semibold">Process sales all day</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Fast checkout, any payment method</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 rounded-xl bg-teal-50 p-4 dark:bg-teal-900/20">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">3</div>
                                    <div>
                                        <div class="text-sm font-semibold">Check stock, adjust inventory</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Same app, no switching</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 rounded-xl bg-teal-50 p-4 dark:bg-teal-900/20">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">4</div>
                                    <div>
                                        <div class="text-sm font-semibold">End shift, review reports</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Cash reconciliation + daily summary</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== CTA ==================== -->
        <section class="py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" data-animate>
                <div class="rounded-3xl bg-gradient-to-r from-teal-600 to-cyan-600 px-8 py-16 text-center text-white shadow-2xl sm:px-16">
                    <h2 class="text-3xl font-bold sm:text-4xl">Ready to Simplify Your Business?</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-teal-100">
                        Join Filipino business owners who switched to OmniPOS and never looked back.
                        One app, everything managed, less hassle.
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

        <!-- ==================== FOOTER ==================== -->
        <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <Link href="/" class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <span class="font-bold">OmniPOS</span>
                        </Link>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            The all-in-one point-of-sale system built for Filipino businesses. Simple, affordable, complete.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Product</h4>
                        <ul class="mt-4 space-y-3">
                            <li><Link href="/" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Home</Link></li>
                            <li><span class="text-sm font-medium text-teal-600">About</span></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Get Started</h4>
                        <ul class="mt-4 space-y-3">
                            <li v-if="canRegister"><Link href="/register" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Create Account</Link></li>
                            <li><Link href="/login" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Sign In</Link></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-12 border-t border-gray-200 pt-8 text-center dark:border-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        &copy; {{ new Date().getFullYear() }} OmniPOS. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.hero-bg {
    background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 50%, #f0f9ff 100%);
}

:root.dark .hero-bg,
.dark .hero-bg {
    background: linear-gradient(135deg, rgba(13, 148, 136, 0.08) 0%, rgba(6, 182, 212, 0.05) 50%, rgba(14, 165, 233, 0.03) 100%);
}

[data-animate] {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}

[data-animate].is-visible {
    opacity: 1;
    transform: translateY(0);
}

@media (prefers-reduced-motion: reduce) {
    [data-animate] {
        opacity: 1;
        transform: none;
        transition: none;
    }
}
</style>
