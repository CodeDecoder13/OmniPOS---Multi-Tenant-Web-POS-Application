<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { Menu, X } from 'lucide-vue-next';

withDefaults(defineProps<{ canRegister?: boolean }>(), { canRegister: true });

const page = usePage();
const mobileMenuOpen = ref(false);
const scrolled = ref(false);

const links = [
    { label: 'Features', href: '/features' },
    { label: 'How It Works', href: '/how-it-works' },
    { label: 'Pricing', href: '/pricing' },
    { label: 'About', href: '/about' },
];

const isActive = (href: string) => page.url.split('?')[0] === href;
const onScroll = () => (scrolled.value = window.scrollY > 10);

let observer: IntersectionObserver | undefined;

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    observer = new IntersectionObserver(
        (entries) => entries.forEach((e) => e.isIntersecting && e.target.classList.add('is-visible')),
        { threshold: 0.1 },
    );
    document.querySelectorAll('[data-animate]').forEach((el) => observer!.observe(el));
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    observer?.disconnect();
});
</script>

<template>
    <div class="min-h-screen bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100">
        <nav
            class="sticky top-0 z-50 border-b backdrop-blur-lg transition-shadow duration-300"
            :class="scrolled
                ? 'border-gray-200 bg-white/80 shadow-lg dark:border-gray-800 dark:bg-gray-950/80'
                : 'border-transparent bg-white/60 dark:bg-gray-950/60'"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-600">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold">OmniPOS</span>
                </Link>

                <div class="hidden items-center gap-8 md:flex">
                    <Link
                        v-for="l in links"
                        :key="l.href"
                        :href="l.href"
                        class="text-sm transition hover:text-teal-600 dark:hover:text-teal-400"
                        :class="isActive(l.href) ? 'font-medium text-teal-600 dark:text-teal-400' : 'text-gray-600 dark:text-gray-400'"
                    >
                        {{ l.label }}
                    </Link>
                </div>

                <div class="hidden items-center gap-3 md:flex">
                    <Link v-if="$page.props.auth.user" href="/dashboard" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link href="/login" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 transition hover:text-teal-600 dark:text-gray-300 dark:hover:text-teal-400">Log in</Link>
                        <Link v-if="canRegister" href="/register" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-700">Get Started</Link>
                    </template>
                </div>

                <button class="md:hidden" aria-label="Toggle menu" @click="mobileMenuOpen = !mobileMenuOpen">
                    <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
            </div>

            <div v-if="mobileMenuOpen" class="border-t border-gray-200 px-4 py-4 md:hidden dark:border-gray-800">
                <div class="flex flex-col gap-3">
                    <Link
                        v-for="l in links"
                        :key="l.href"
                        :href="l.href"
                        class="text-sm"
                        :class="isActive(l.href) ? 'font-medium text-teal-600' : 'text-gray-600 dark:text-gray-400'"
                    >
                        {{ l.label }}
                    </Link>
                    <hr class="border-gray-200 dark:border-gray-800" />
                    <Link v-if="$page.props.auth.user" href="/dashboard" class="text-sm font-medium text-teal-600">Dashboard</Link>
                    <template v-else>
                        <Link href="/login" class="text-sm text-gray-700 dark:text-gray-300">Log in</Link>
                        <Link v-if="canRegister" href="/register" class="rounded-lg bg-teal-600 px-4 py-2 text-center text-sm font-medium text-white">Get Started</Link>
                    </template>
                </div>
            </div>
        </nav>

        <slot />

        <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50" role="contentinfo">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-6 sm:flex-row">
                    <p class="max-w-sm text-sm text-gray-500 dark:text-gray-400">
                        The all-in-one point-of-sale system built for Filipino businesses. Manage, sell, and grow.
                    </p>
                    <div class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-gray-500 dark:text-gray-400">
                        <Link v-for="l in links" :key="l.href" :href="l.href" class="hover:text-teal-600">{{ l.label }}</Link>
                        <Link href="/privacy" class="hover:text-teal-600">Privacy</Link>
                        <Link href="/terms" class="hover:text-teal-600">Terms</Link>
                    </div>
                </div>
                <p class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">&copy; {{ new Date().getFullYear() }} OmniPOS. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>

<style>
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

.hero-bg {
    background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 50%, #f0f9ff 100%);
}

.dark .hero-bg {
    background: linear-gradient(135deg, rgba(13, 148, 136, 0.08) 0%, rgba(6, 182, 212, 0.05) 50%, rgba(14, 165, 233, 0.03) 100%);
}
</style>
