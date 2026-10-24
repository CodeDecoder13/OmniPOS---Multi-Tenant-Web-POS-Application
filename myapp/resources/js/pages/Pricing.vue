<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Check, ChevronDown, ShieldCheck, Wallet, Layers, Rocket } from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { Plan } from '@/types';

const props = withDefaults(defineProps<{ canRegister?: boolean; plans: Plan[] }>(), { canRegister: true });

const ANNUAL_DISCOUNT = 20; // percent off when billed yearly
const billing = ref<'monthly' | 'annual'>('monthly');
const peso = (n: number) => `₱${Math.round(n).toLocaleString('en-PH')}`;
const monthly = (p: Plan) => Number(p.price) * (billing.value === 'annual' ? 1 - ANNUAL_DISCOUNT / 100 : 1);
const limit = (n: number | null, unit: string) => (n === null ? `Unlimited ${unit}` : `${n.toLocaleString('en-PH')} ${unit}`);

const comparison = [
    { label: 'Branches', get: (p: Plan) => limit(p.max_branches, '') },
    { label: 'Users', get: (p: Plan) => limit(p.max_users, '') },
    { label: 'Products', get: (p: Plan) => limit(p.max_products, '') },
];

const value = [
    { icon: Layers, title: 'Replaces three tools', body: 'POS, inventory, and reporting in one subscription instead of three that don’t sync.' },
    { icon: Wallet, title: 'No per-transaction fees', body: 'Pay a flat price for your plan. We don’t take a cut of your sales.' },
    { icon: ShieldCheck, title: 'Keep your data', body: 'Upgrade or change plans any time. Your products, customers, and history stay put.' },
    { icon: Rocket, title: 'Start free, stay free', body: 'The Free plan has no time limit. Move up only when your business needs more.' },
];

const faqs = [
    { q: 'Is the Free plan really free?', a: 'Yes. It has no time limit and needs no credit card. It covers one branch with the limits shown above.' },
    { q: 'How does the annual discount work?', a: `Choose Annual and you save ${ANNUAL_DISCOUNT}% compared with paying month to month. The yearly total is shown on each plan.` },
    { q: 'Do paid plans need a promo code?', a: 'Paid plans are activated with a valid promo code when you register. If you have one, enter it during sign-up.' },
    { q: 'Can I switch plans later?', a: 'Yes. You can move between plans at any time without losing data.' },
    { q: 'Which payment methods can my customers use?', a: 'Cash, card, GCash, Maya, bank transfer, and split payments, on every plan.' },
];

const open = ref<number | null>(null);
</script>

<template>
    <Head title="Pricing — Simple OmniPOS Plans, Free to Start">
        <meta name="description" content="OmniPOS pricing in Philippine pesos. Start free, upgrade when you grow, and save 20% with annual billing. No per-transaction fees." />
        <link rel="canonical" href="https://omnipos.shop/pricing" />
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <PublicLayout :can-register="canRegister">
        <section class="relative overflow-hidden">
            <div class="hero-bg absolute inset-0"></div>
            <div class="relative mx-auto max-w-4xl px-4 pb-10 pt-20 text-center sm:px-6 sm:pt-24 lg:px-8" data-animate>
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                    Simple pricing that
                    <span class="bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent dark:from-teal-400 dark:to-cyan-400">grows with you</span>
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg text-gray-600 dark:text-gray-400">
                    Start free. Upgrade when you add branches, staff, or products. No hidden fees and no cut of your sales.
                </p>

                <div class="mt-8 flex justify-center">
                    <div class="inline-flex items-center rounded-full border border-gray-200 bg-white p-1 text-sm font-semibold dark:border-gray-800 dark:bg-gray-900">
                        <button type="button" class="rounded-full px-5 py-2 transition" :class="billing === 'monthly' ? 'bg-teal-600 text-white shadow' : 'text-gray-600 dark:text-gray-400'" @click="billing = 'monthly'">Monthly</button>
                        <button type="button" class="flex items-center gap-2 rounded-full px-5 py-2 transition" :class="billing === 'annual' ? 'bg-teal-600 text-white shadow' : 'text-gray-600 dark:text-gray-400'" @click="billing = 'annual'">
                            Annual
                            <span class="rounded-full px-2 py-0.5 text-xs" :class="billing === 'annual' ? 'bg-white/20 text-white' : 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'">Save {{ ANNUAL_DISCOUNT }}%</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Plans -->
        <section class="pb-16 pt-6">
            <div class="mx-auto grid max-w-6xl gap-8 px-4 sm:px-6 md:grid-cols-3 lg:px-8" data-animate>
                <div
                    v-for="plan in props.plans"
                    :key="plan.slug"
                    class="relative flex flex-col rounded-2xl border bg-white p-8 transition hover:shadow-xl dark:bg-gray-900"
                    :class="plan.slug === 'pro'
                        ? 'border-teal-600 shadow-lg shadow-teal-600/10 ring-2 ring-teal-600 dark:border-teal-500 dark:ring-teal-500'
                        : 'border-gray-200 dark:border-gray-800'"
                >
                    <div v-if="plan.slug === 'pro'" class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-teal-600 px-4 py-1 text-xs font-semibold text-white">Most Popular</div>
                    <h2 class="text-xl font-bold">{{ plan.name }}</h2>
                    <div class="mt-4">
                        <span class="text-4xl font-extrabold">{{ peso(monthly(plan)) }}</span>
                        <span v-if="Number(plan.price) > 0" class="text-gray-500">/month</span>
                        <span v-else class="text-gray-500"> forever</span>
                        <p v-if="billing === 'annual' && Number(plan.price) > 0" class="mt-1 text-sm text-gray-500">
                            <span class="line-through">{{ peso(Number(plan.price)) }}</span>
                            · {{ peso(monthly(plan) * 12) }} billed yearly
                        </p>
                    </div>
                    <ul class="mt-8 flex-1 space-y-3">
                        <li v-for="f in plan.features" :key="f" class="flex items-start gap-3 text-sm">
                            <Check class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />{{ f }}
                        </li>
                    </ul>
                    <Link
                        v-if="canRegister"
                        :href="`/register?plan=${plan.slug}&billing=${billing}`"
                        class="mt-8 block rounded-lg py-3 text-center text-sm font-semibold transition"
                        :class="plan.slug === 'pro'
                            ? 'bg-teal-600 text-white shadow-md hover:bg-teal-700'
                            : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800'"
                    >
                        {{ Number(plan.price) === 0 ? 'Get Started Free' : 'Get Started' }}
                    </Link>
                </div>
            </div>
        </section>

        <!-- Limits comparison -->
        <section class="border-y border-gray-200 bg-gray-50/60 py-16 dark:border-gray-800 dark:bg-gray-900/30">
            <div class="mx-auto max-w-4xl px-4 sm:px-6" data-animate>
                <h2 class="text-center text-3xl font-bold">Compare plan limits</h2>
                <div class="mt-8 overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="px-5 py-3 font-semibold"></th>
                                <th v-for="p in props.plans" :key="p.slug" class="px-5 py-3 font-semibold">{{ p.name }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in comparison" :key="row.label" class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ row.label }}</td>
                                <td v-for="p in props.plans" :key="p.slug" class="px-5 py-3">{{ row.get(p) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-4 text-center text-sm text-gray-500">Plan feature lists above show everything included at each level.</p>
            </div>
        </section>

        <!-- Value -->
        <section class="py-16 sm:py-20">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-center text-3xl font-bold" data-animate>Why owners switch to OmniPOS</h2>
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4" data-animate>
                    <div v-for="v in value" :key="v.title" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                        <component :is="v.icon" class="h-6 w-6 text-teal-600" />
                        <h3 class="mt-3 font-semibold">{{ v.title }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ v.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="border-t border-gray-200 bg-gray-50/50 py-16 dark:border-gray-800 dark:bg-gray-900/30">
            <div class="mx-auto max-w-3xl px-4 sm:px-6">
                <h2 class="text-center text-3xl font-bold" data-animate>Pricing questions</h2>
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
                <h2 class="text-3xl font-bold">Not sure which plan? Start free.</h2>
                <p class="mt-3 text-gray-600 dark:text-gray-400">You can upgrade the day you need more.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <Link v-if="canRegister" href="/register" class="rounded-lg bg-teal-600 px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-teal-700">Create Free Account</Link>
                    <Link href="/features" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Explore Features</Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
