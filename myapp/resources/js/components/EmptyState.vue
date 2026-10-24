<script setup lang="ts">
import type { Component } from 'vue';

/** Empty list or filter result. Say what is missing and how to fix it; put the action in the default slot. */
defineProps<{
    icon: Component;
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-xl border bg-card px-4 py-16 text-center">
        <div class="rounded-full bg-muted p-4">
            <component :is="icon" class="size-8 text-muted-foreground" aria-hidden="true" />
        </div>
        <h3 class="mt-4 text-lg font-semibold">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-md text-sm text-muted-foreground">{{ description }}</p>
        <div v-if="$slots.default" class="mt-4"><slot /></div>
    </div>
</template>
