<script lang="ts">
// Shared across every InfoTip: when one just closed, the next opens with no delay.
let lastClosedAt = 0;
</script>

<script setup lang="ts">
import { CircleHelp } from 'lucide-vue-next';
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui';
import { onBeforeUnmount, ref } from 'vue';

/**
 * "What does this mean?" help icon. Hover or focus-and-Enter on desktop, tap on touch.
 * Use the default slot for richer content; `text` covers the one-liner case.
 */
const props = withDefaults(
    defineProps<{
        text?: string;
        label?: string;
        side?: 'top' | 'right' | 'bottom' | 'left';
    }>(),
    { label: 'More info', side: 'top' },
);

const open = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

const hoverCapable = typeof window !== 'undefined' && window.matchMedia?.('(hover: hover) and (pointer: fine)').matches;

function onEnter() {
    if (!hoverCapable) return;
    clearTimeout(timer);
    const warm = Date.now() - lastClosedAt < 300;
    timer = setTimeout(() => (open.value = true), warm ? 0 : 400);
}

function onLeave() {
    if (!hoverCapable) return;
    clearTimeout(timer);
    timer = setTimeout(() => (open.value = false), 100);
}

function onOpenChange(value: boolean) {
    if (!value) lastClosedAt = Date.now();
    open.value = value;
}

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <PopoverRoot :open="open" @update:open="onOpenChange">
        <PopoverTrigger as-child>
            <button
                type="button"
                :aria-label="props.label"
                class="inline-flex size-5 shrink-0 cursor-help items-center justify-center rounded-full text-muted-foreground outline-none transition-colors hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                @mouseenter="onEnter"
                @mouseleave="onLeave"
            >
                <CircleHelp class="size-3.5" />
            </button>
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent
                :side="props.side"
                :side-offset="6"
                class="z-50 max-w-xs origin-(--reka-popover-content-transform-origin) rounded-md bg-foreground px-3 py-1.5 text-xs text-balance text-background animate-in fade-in-0 zoom-in-95 duration-150 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95"
                @mouseenter="onEnter"
                @mouseleave="onLeave"
                @open-auto-focus.prevent
            >
                <slot>{{ props.text }}</slot>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
