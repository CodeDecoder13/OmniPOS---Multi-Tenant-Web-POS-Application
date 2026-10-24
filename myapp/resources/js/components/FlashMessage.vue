<script setup lang="ts">
import { CircleAlert, CircleCheck, Info, X } from 'lucide-vue-next';
import { watch } from 'vue';
import { useFlash } from '@/composables/useFlash';
import { dismissToast, toast, useToasts } from '@/composables/useToast';

// Server flash messages become toasts; pages can also call toast.*() directly.
const { flash } = useFlash();
const { toasts } = useToasts();

watch(
    flash,
    (val) => {
        if (val.success) toast.success(val.success);
        else if (val.error) toast.error(val.error);
    },
    { immediate: true },
);

const icons = { success: CircleCheck, error: CircleAlert, info: Info };
const tones = {
    success: 'border-green-200 bg-green-50 text-green-900 dark:border-green-900 dark:bg-green-950 dark:text-green-100',
    error: 'border-red-200 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100',
    info: 'border-border bg-card text-card-foreground',
};

function runAction(id: number, onClick: () => void) {
    onClick();
    dismissToast(id);
}
</script>

<template>
    <TransitionGroup
        tag="div"
        class="pointer-events-none fixed top-4 right-4 z-[100] flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-2"
        enter-active-class="transition duration-200 ease-out motion-reduce:transition-opacity"
        enter-from-class="translate-x-4 opacity-0 motion-reduce:translate-x-0"
        leave-active-class="transition duration-150 ease-out motion-reduce:transition-opacity"
        leave-to-class="opacity-0"
    >
        <div
            v-for="t in toasts"
            :key="t.id"
            :role="t.type === 'error' ? 'alert' : 'status'"
            class="pointer-events-auto flex items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg"
            :class="tones[t.type]"
        >
            <component :is="icons[t.type]" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <span class="flex-1 break-words">{{ t.message }}</span>
            <button
                v-if="t.action"
                type="button"
                class="shrink-0 font-semibold underline underline-offset-2 outline-none focus-visible:ring-2 focus-visible:ring-ring"
                @click="runAction(t.id, t.action.onClick)"
            >
                {{ t.action.label }}
            </button>
            <button
                type="button"
                aria-label="Dismiss notification"
                class="-mr-1 shrink-0 rounded p-1 opacity-70 outline-none hover:opacity-100 focus-visible:ring-2 focus-visible:ring-ring"
                @click="dismissToast(t.id)"
            >
                <X class="size-4" />
            </button>
        </div>
    </TransitionGroup>
</template>
