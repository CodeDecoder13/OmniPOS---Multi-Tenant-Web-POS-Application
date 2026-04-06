<script setup lang="ts">
import { ShieldX } from 'lucide-vue-next';

defineProps<{
    tenantName: string;
}>();
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gradient-to-br from-teal-50 to-teal-100 dark:from-gray-950 dark:to-gray-900">
        <div class="flex w-full max-w-md flex-col items-center gap-6 px-4">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <img src="/images/icon.png" alt="OmniPOS" class="h-10 w-10" />
                <span class="text-2xl font-bold tracking-tight text-teal-600 dark:text-teal-400">OmniPOS</span>
            </div>

            <!-- Card -->
            <div class="w-full rounded-xl border border-gray-200 bg-white p-8 text-center shadow-lg dark:border-gray-800 dark:bg-gray-900">
                <!-- Icon -->
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <ShieldX class="h-8 w-8 text-red-600 dark:text-red-400" />
                </div>

                <!-- Heading -->
                <h1 class="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">Account Deactivated</h1>

                <!-- Body -->
                <p class="mb-6 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    Your organization <strong class="font-medium text-gray-900 dark:text-gray-100">{{ tenantName }}</strong>
                    has been deactivated. Please contact the OmniPOS support team for assistance.
                </p>

                <!-- Support email -->
                <a
                    href="mailto:support@omnipos.shop"
                    class="mb-6 inline-block text-sm font-medium text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300"
                >
                    support@omnipos.shop
                </a>

                <!-- Divider -->
                <div class="my-6 border-t border-gray-200 dark:border-gray-800"></div>

                <!-- Dashboard link -->
                <a
                    href="/dashboard"
                    class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-medium text-white transition-colors hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-600"
                >
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</template>
