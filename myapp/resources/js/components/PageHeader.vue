<script setup lang="ts">
import type { Component } from 'vue';

/** Icon tile, title, one-line description, and an actions slot. */
defineProps<{
    icon: Component;
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <component :is="icon" class="size-5" aria-hidden="true" />
            </div>
            <div>
                <h1 class="text-2xl font-bold">{{ title }}</h1>
                <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
            </div>
        </div>
        <div v-if="$slots.default" class="flex items-center gap-2"><slot /></div>
    </div>
</template>
