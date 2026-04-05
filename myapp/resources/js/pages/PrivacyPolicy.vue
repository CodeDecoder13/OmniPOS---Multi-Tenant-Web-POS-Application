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
    <Head title="Privacy Policy - OmniPOS">
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
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Privacy Policy</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Last updated: April 5, 2026</p>

            <div class="prose prose-gray mt-10 max-w-none dark:prose-invert prose-headings:font-semibold prose-headings:tracking-tight prose-a:text-teal-600">
                <h2>1. Introduction</h2>
                <p>
                    Welcome to OmniPOS ("we," "our," or "us"). We are committed to protecting your privacy and ensuring the security of your personal information.
                    This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our multi-tenant web-based point-of-sale application and related services.
                </p>

                <h2>2. Information We Collect</h2>

                <h3>2.1 Account Information</h3>
                <p>When you register and use OmniPOS, we collect:</p>
                <ul>
                    <li><strong>Personal details:</strong> Name, email address, and password (hashed)</li>
                    <li><strong>Business details:</strong> Business name, branch information, and business type</li>
                    <li><strong>Google account data:</strong> If you sign in via Google OAuth, we receive your Google profile name, email, and unique Google ID</li>
                </ul>

                <h3>2.2 Tenant & Business Data</h3>
                <p>As a multi-tenant platform, we store data you create within your tenant workspace, including:</p>
                <ul>
                    <li>Products, categories, and inventory records</li>
                    <li>Sales transactions and order history</li>
                    <li>Customer records (if added)</li>
                    <li>Employee/user accounts, roles, and permissions within your tenant</li>
                    <li>Branch and supplier information</li>
                    <li>Financial reports and analytics data</li>
                </ul>

                <h3>2.3 Technical & Usage Data</h3>
                <ul>
                    <li>IP address and browser/device information (collected for login alerts and security)</li>
                    <li>Pages visited and features used</li>
                    <li>Session cookies and authentication tokens</li>
                </ul>

                <h2>3. How We Use Your Information</h2>
                <p>We use collected information to:</p>
                <ul>
                    <li>Provide, maintain, and improve the OmniPOS platform</li>
                    <li>Authenticate your identity and manage your account (including two-factor authentication)</li>
                    <li>Process transactions and generate business reports</li>
                    <li>Send essential communications such as email verification OTPs, login alerts, and welcome notifications</li>
                    <li>Enforce subscription plans, usage limits, and billing</li>
                    <li>Detect and prevent fraud, abuse, or security threats</li>
                    <li>Comply with legal obligations</li>
                </ul>

                <h2>4. Data Isolation & Multi-Tenancy</h2>
                <p>
                    OmniPOS operates as a multi-tenant application. All tenant data is logically isolated using tenant-scoped identifiers.
                    Your business data is only accessible to authorized users within your tenant. Other tenants cannot view, access, or modify your data.
                </p>

                <h2>5. Cookies & Local Storage</h2>
                <p>We use <strong>essential cookies</strong> to:</p>
                <ul>
                    <li>Maintain your authenticated session</li>
                    <li>Store CSRF tokens for security</li>
                    <li>Remember your preferences (e.g., dark mode, sidebar state)</li>
                </ul>
                <p>
                    We do not use third-party advertising or tracking cookies. You may manage cookie preferences through the cookie consent dialog presented on your first visit.
                </p>

                <h2>6. Data Sharing & Disclosure</h2>
                <p>We do <strong>not</strong> sell, rent, or trade your personal information. We may share data only in these circumstances:</p>
                <ul>
                    <li><strong>Service providers:</strong> Trusted third-party services that help us operate the platform (e.g., email delivery, hosting)</li>
                    <li><strong>Google OAuth:</strong> If you choose to sign in with Google, authentication data is exchanged with Google per their privacy policy</li>
                    <li><strong>Legal requirements:</strong> When required by law, regulation, or valid legal process</li>
                    <li><strong>Business transfers:</strong> In connection with a merger, acquisition, or sale of assets</li>
                </ul>

                <h2>7. Data Security</h2>
                <p>We implement industry-standard security measures including:</p>
                <ul>
                    <li>Password hashing using bcrypt</li>
                    <li>Two-factor authentication (2FA) support</li>
                    <li>HTTPS encryption for all data in transit</li>
                    <li>CSRF protection on all forms and state-changing requests</li>
                    <li>Login alerts with IP and device information</li>
                    <li>Role-based access control within each tenant</li>
                </ul>

                <h2>8. Data Retention</h2>
                <p>
                    We retain your account and business data for as long as your account is active or as needed to provide our services.
                    If you request account deletion, we will remove your personal data within a reasonable timeframe, subject to legal retention obligations.
                    Transaction records may be retained as required by applicable tax and business regulations.
                </p>

                <h2>9. Your Rights</h2>
                <p>Depending on your jurisdiction, you may have the right to:</p>
                <ul>
                    <li>Access the personal data we hold about you</li>
                    <li>Correct inaccurate or incomplete information</li>
                    <li>Request deletion of your personal data</li>
                    <li>Export your business data</li>
                    <li>Withdraw consent for optional data processing</li>
                </ul>
                <p>To exercise these rights, contact us using the information provided below.</p>

                <h2>10. Children's Privacy</h2>
                <p>
                    OmniPOS is not intended for use by individuals under the age of 18. We do not knowingly collect personal information from children.
                </p>

                <h2>11. Changes to This Policy</h2>
                <p>
                    We may update this Privacy Policy from time to time. We will notify registered users of significant changes via email or in-app notification.
                    Continued use of OmniPOS after changes constitutes acceptance of the updated policy.
                </p>

                <h2>12. Contact Us</h2>
                <p>
                    If you have questions or concerns about this Privacy Policy or our data practices, please contact us at:
                </p>
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
                            <li><span class="text-sm font-medium text-teal-600">Privacy Policy</span></li>
                            <li><Link href="/terms" class="text-sm text-gray-500 hover:text-teal-600 dark:text-gray-400">Terms of Use</Link></li>
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
