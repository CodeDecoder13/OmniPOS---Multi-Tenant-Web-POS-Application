<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

/**
 * Icon-only button that always has a name: the label is both the accessible
 * name and the hover/focus tooltip. Pass `href` to render an Inertia link.
 */
defineOptions({ inheritAttrs: false });

withDefaults(
    defineProps<{
        label: string;
        href?: string;
        variant?: ButtonVariants['variant'];
        size?: ButtonVariants['size'];
    }>(),
    { variant: 'ghost', size: 'icon-sm' },
);
</script>

<template>
    <!-- 400ms to open, then neighbours open instantly (skip delay). -->
    <TooltipProvider :delay-duration="400" :skip-delay-duration="300">
        <Tooltip>
            <TooltipTrigger as-child>
                <Button v-if="href" as-child :variant="variant" :size="size" v-bind="$attrs">
                    <Link :href="href" :aria-label="label"><slot /></Link>
                </Button>
                <Button v-else type="button" :variant="variant" :size="size" :aria-label="label" v-bind="$attrs">
                    <slot />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ label }}</TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
