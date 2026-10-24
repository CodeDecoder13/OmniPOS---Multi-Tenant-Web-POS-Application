<script setup lang="ts">
import type { Component } from 'vue';

/**
 * Number + label tile. Add `toggle` to make it a button that acts as a filter
 * (`selected` shows whether the filter is on).
 */
withDefaults(
    defineProps<{
        icon: Component;
        label: string;
        value: number | string;
        tone?: 'indigo' | 'red' | 'emerald' | 'purple' | 'amber' | 'sky';
        toggle?: boolean;
        selected?: boolean;
    }>(),
    { tone: 'indigo' },
);

defineEmits<{ click: [] }>();

const tones = {
    indigo: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400',
    red: 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400',
    emerald: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400',
    amber: 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400',
    sky: 'bg-sky-100 text-sky-600 dark:bg-sky-900/40 dark:text-sky-400',
};

</script>

<template>
    <component
        :is="toggle ? 'button' : 'div'"
        :type="toggle ? 'button' : undefined"
        :aria-pressed="toggle ? selected : undefined"
        class="flex items-center gap-3 rounded-xl border bg-card p-4 text-left"
        :class="toggle
            ? ['cursor-pointer outline-none transition-colors hover:bg-muted/50 focus-visible:ring-[3px] focus-visible:ring-ring/50 active:scale-[0.98]', selected ? 'border-primary/50 bg-primary/5' : '']
            : ''"
        @click="toggle && $emit('click')"
    >
        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg" :class="tones[tone]">
            <component :is="icon" class="size-[18px]" aria-hidden="true" />
        </div>
        <div>
            <p class="text-2xl font-bold tabular-nums">{{ value }}</p>
            <p class="text-xs text-muted-foreground">{{ label }}</p>
        </div>
    </component>
</template>
