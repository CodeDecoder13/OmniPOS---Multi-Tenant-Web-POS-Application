<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { Menu, X } from 'lucide-vue-next';

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
    onUnmounted(() => window.removeEventListener('scroll', handleScroll));
});
</script>

<template>
    <Head title="Terms of Use - OmniPOS">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100">
        <!-- NAVBAR -->
        <nav
            class="sticky top-0 z-50 border-b backdrop-blur-lg transition-shadow duration-300"
            :class="scrolled
                ? 'border-gray-200 bg-white/80 shadow-lg dark:border-gray-800 dark:bg-gray-950/80'
                : 'border-transparent bg-white/60 dark:bg-gray-950/60'"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                        <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold">OmniPOS</span>
                </Link>

                <div class="hidden items-center gap-6 md:flex">
                    <Link href="/" class="text-sm font-medium text-gray-600 hover:text-teal-600 dark:text-gray-300">Home</Link>
                    <Link href="/about" class="text-sm font-medium text-gray-600 hover:text-teal-600 dark:text-gray-300">About</Link>
                    <Link href="/login" class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">Sign In</Link>
                </div>

                <button class="md:hidden" @click="mobileMenuOpen = !mobileMenuOpen">
                    <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
            </div>

            <div v-if="mobileMenuOpen" class="border-t border-gray-200 bg-white px-4 pb-4 pt-2 dark:border-gray-800 dark:bg-gray-950 md:hidden">
                <Link href="/" class="block py-2 text-sm text-gray-600 dark:text-gray-300">Home</Link>
                <Link href="/about" class="block py-2 text-sm text-gray-600 dark:text-gray-300">About</Link>
                <Link href="/login" class="mt-2 block rounded-lg bg-teal-600 px-4 py-2.5 text-center text-sm font-semibold text-white">Sign In</Link>
            </div>
        </nav>

        <!-- CONTENT -->
        <main class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Terms of Use</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Last updated: April 5, 2026</p>

            <div class="prose prose-gray mt-10 max-w-none dark:prose-invert prose-headings:font-semibold prose-headings:tracking-tight prose-a:text-teal-600">
                <h2>1. Acceptance of Terms</h2>
                <p>
                    By accessing or using OmniPOS ("the Service"), you agree to be bound by these Terms of Use.
                    If you do not agree to these terms, you may not use the Service. These terms apply to all users, including business owners, employees, and administrators.
                </p>

                <h2>2. Description of Service</h2>
                <p>
                    OmniPOS is a multi-tenant, web-based point-of-sale (POS) application designed for Filipino businesses.
                    The Service provides tools for sales processing, inventory management, employee management, branch operations, reporting, and more.
                    Each business operates within its own isolated tenant workspace with path-based access.
                </p>

                <h2>3. Account Registration</h2>
                <ul>
                    <li>You must provide accurate and complete information when creating an account</li>
                    <li>You are responsible for maintaining the confidentiality of your account credentials</li>
                    <li>You may register using an email/password combination or through Google OAuth</li>
                    <li>You must be at least 18 years old to create an account</li>
                    <li>One person may not maintain multiple accounts for the same tenant</li>
                    <li>You are responsible for all activities that occur under your account</li>
                </ul>

                <h2>4. Tenant & Business Workspace</h2>
                <p>When you create a business on OmniPOS:</p>
                <ul>
                    <li>A dedicated tenant workspace is created for your business</li>
                    <li>You (the owner) have full administrative control over your workspace</li>
                    <li>You may invite users, assign roles, and manage permissions within your tenant</li>
                    <li>Each tenant's data is logically isolated from other tenants</li>
                    <li>You are responsible for the data entered and actions taken within your tenant</li>
                </ul>

                <h2>5. Subscription Plans & Billing</h2>
                <ul>
                    <li>OmniPOS offers different subscription plans with varying features, branch limits, user limits, and product limits</li>
                    <li>Free trial or free-tier plans may be available with limited functionality</li>
                    <li>Paid plans are billed according to the pricing displayed at the time of subscription</li>
                    <li>We reserve the right to modify pricing with reasonable advance notice to existing subscribers</li>
                    <li>Exceeding plan limits (branches, users, products) may restrict certain features until you upgrade</li>
                    <li>Promo codes are subject to their own terms and expiration dates</li>
                </ul>

                <h2>6. Acceptable Use</h2>
                <p>You agree <strong>not</strong> to:</p>
                <ul>
                    <li>Use the Service for any unlawful purpose or to facilitate illegal activities</li>
                    <li>Attempt to gain unauthorized access to other tenants' data or system resources</li>
                    <li>Reverse engineer, decompile, or disassemble any part of the Service</li>
                    <li>Upload malicious code, viruses, or harmful content</li>
                    <li>Abuse the platform through automated scripts, bots, or scraping</li>
                    <li>Misrepresent your identity or affiliation</li>
                    <li>Resell or redistribute the Service without written permission</li>
                    <li>Interfere with the Service's performance or other users' experience</li>
                </ul>

                <h2>7. Data Ownership</h2>
                <ul>
                    <li><strong>Your data:</strong> You retain ownership of all business data you enter into OmniPOS (products, transactions, customer records, etc.)</li>
                    <li><strong>Our platform:</strong> We retain all rights to the OmniPOS software, design, code, and infrastructure</li>
                    <li>We do not claim ownership of your business data and will not use it for purposes beyond providing the Service</li>
                    <li>You grant us a limited license to store, process, and display your data solely to operate the Service</li>
                </ul>

                <h2>8. Security & Two-Factor Authentication</h2>
                <ul>
                    <li>We provide security features including two-factor authentication (2FA) and login alerts</li>
                    <li>You are encouraged to enable 2FA for enhanced account security</li>
                    <li>You are responsible for keeping your recovery codes safe if you enable 2FA</li>
                    <li>We are not liable for unauthorized access resulting from your failure to secure your credentials</li>
                </ul>

                <h2>9. Service Availability</h2>
                <ul>
                    <li>We strive to maintain high availability but do not guarantee uninterrupted access</li>
                    <li>The Service may be temporarily unavailable for maintenance, updates, or unforeseen issues</li>
                    <li>We are not liable for any losses resulting from Service downtime</li>
                    <li>As the platform is currently in its <strong>testing phase</strong>, features may change and bugs may occur</li>
                </ul>

                <h2>10. Limitation of Liability</h2>
                <p>
                    To the maximum extent permitted by law, OmniPOS and its developers shall not be liable for any indirect, incidental, special,
                    consequential, or punitive damages, including but not limited to loss of profits, data, or business opportunities,
                    arising from your use of or inability to use the Service.
                </p>
                <p>
                    The Service is provided "as is" and "as available" without warranties of any kind, either express or implied.
                </p>

                <h2>11. Account Termination</h2>
                <ul>
                    <li>You may delete your account at any time through the account settings</li>
                    <li>We reserve the right to suspend or terminate accounts that violate these Terms</li>
                    <li>Upon termination, your tenant data may be retained for a limited period before permanent deletion</li>
                    <li>We will make reasonable efforts to notify you before involuntary termination</li>
                </ul>

                <h2>12. Intellectual Property</h2>
                <p>
                    All content, trademarks, logos, and intellectual property related to OmniPOS are owned by us or our licensors.
                    You may not use our branding or trademarks without prior written consent.
                </p>

                <h2>13. Changes to Terms</h2>
                <p>
                    We may update these Terms of Use from time to time. Significant changes will be communicated via email or in-app notification.
                    Continued use of the Service after changes constitutes acceptance of the updated terms.
                </p>

                <h2>14. Governing Law</h2>
                <p>
                    These Terms shall be governed by and construed in accordance with the laws of the Republic of the Philippines.
                    Any disputes arising from these Terms or your use of the Service shall be resolved in the appropriate courts of the Philippines.
                </p>

                <h2>15. Contact Us</h2>
                <p>If you have questions about these Terms of Use, please contact us at:</p>
                <ul>
                    <li><strong>Email:</strong> rhuzzel.paramio@omnipos.shop</li>
                </ul>
            </div>
        </main>

        <!-- FOOTER -->
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
                            The all-in-one point-of-sale system built for Filipino businesses.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Product</h4>
                        <ul class="mt-4 space-y-3">
                            <li><Link href="/" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Home</Link></li>
                            <li><Link href="/about" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">About</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Legal</h4>
                        <ul class="mt-4 space-y-3">
                            <li><Link href="/privacy" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Privacy Policy</Link></li>
                            <li><span class="text-sm font-medium text-teal-600">Terms of Use</span></li>
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
