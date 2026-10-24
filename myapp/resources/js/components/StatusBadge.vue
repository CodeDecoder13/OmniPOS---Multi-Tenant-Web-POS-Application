<script setup lang="ts">
import { computed } from 'vue';

/** One status-to-color map for orders, purchase orders, transfers, tables and users. */
const props = defineProps<{ status: string; label?: string }>();

const tones: Record<string, string> = {
    success: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    warning: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    danger: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    info: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
    accent: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
    neutral: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
};

const toneFor: Record<string, keyof typeof tones> = {
    completed: 'success', received: 'success', active: 'success', available: 'success', ready: 'success', served: 'success',
    pending: 'warning', draft: 'neutral', partial: 'warning', in_transit: 'info', sent: 'info', preparing: 'info', occupied: 'warning',
    voided: 'danger', cancelled: 'danger', inactive: 'neutral',
    refunded: 'accent',
};

const text = computed(() => props.label ?? props.status.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase()));
</script>

<template>
    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="tones[toneFor[status] ?? 'neutral']">{{ text }}</span>
</template>
