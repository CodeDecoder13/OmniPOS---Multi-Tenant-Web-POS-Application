<script setup lang="ts">
import { computed } from 'vue';
import { Check, Crown, Sparkles } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface PlanData {
    name: string;
    slug: string;
    price: string;
    features: string[];
    max_branches: number | null;
    max_users: number | null;
    max_products: number | null;
}

const props = defineProps<{
    open: boolean;
    currentPlanSlug: string;
    plans: PlanData[];
    resource: 'branches' | 'users' | 'products';
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const resourceLabel = computed(() => {
    const labels = { branches: 'branch', users: 'user', products: 'product' };
    return labels[props.resource];
});

const currentPlan = computed(() => props.plans.find((p) => p.slug === props.currentPlanSlug));

function formatLimit(value: number | null): string {
    return value === null ? 'Unlimited' : String(value);
}

function formatPrice(price: string): string {
    const num = Number(price);
    if (num === 0) return 'Free';
    return `$${num.toFixed(2)}/mo`;
}

function isCurrentPlan(plan: PlanData): boolean {
    return plan.slug === props.currentPlanSlug;
}

function isUpgrade(plan: PlanData): boolean {
    return Number(plan.price) > Number(currentPlan.value?.price ?? 0);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Crown class="h-5 w-5 text-amber-500" />
                    Upgrade Your Plan
                </DialogTitle>
                <DialogDescription>
                    You've reached the {{ resourceLabel }} limit on your
                    {{ currentPlan?.name ?? 'current' }} plan. Upgrade to unlock more.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4" :class="plans.length >= 3 ? 'sm:grid-cols-3' : plans.length === 2 ? 'sm:grid-cols-2' : ''">
                <div
                    v-for="plan in plans"
                    :key="plan.slug"
                    class="relative flex flex-col rounded-xl border p-4 transition-shadow hover:shadow-md"
                    :class="isCurrentPlan(plan) ? 'border-primary bg-primary/5' : isUpgrade(plan) ? 'border-amber-300 dark:border-amber-700' : ''"
                >
                    <!-- Current plan badge -->
                    <div
                        v-if="isCurrentPlan(plan)"
                        class="absolute -top-2.5 left-1/2 -translate-x-1/2 rounded-full bg-primary px-3 py-0.5 text-xs font-medium text-primary-foreground"
                    >
                        Current Plan
                    </div>

                    <!-- Plan header -->
                    <div class="mb-3 text-center">
                        <h3 class="text-lg font-semibold">{{ plan.name }}</h3>
                        <p class="mt-1 text-2xl font-bold">
                            {{ formatPrice(plan.price) }}
                        </p>
                    </div>

                    <!-- Resource limits -->
                    <div class="mb-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between rounded-md bg-muted/50 px-3 py-1.5">
                            <span class="text-muted-foreground">Branches</span>
                            <span class="font-medium" :class="resource === 'branches' && !isCurrentPlan(plan) && isUpgrade(plan) ? 'text-amber-600 dark:text-amber-400' : ''">
                                {{ formatLimit(plan.max_branches) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between rounded-md bg-muted/50 px-3 py-1.5">
                            <span class="text-muted-foreground">Users</span>
                            <span class="font-medium" :class="resource === 'users' && !isCurrentPlan(plan) && isUpgrade(plan) ? 'text-amber-600 dark:text-amber-400' : ''">
                                {{ formatLimit(plan.max_users) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between rounded-md bg-muted/50 px-3 py-1.5">
                            <span class="text-muted-foreground">Products</span>
                            <span class="font-medium" :class="resource === 'products' && !isCurrentPlan(plan) && isUpgrade(plan) ? 'text-amber-600 dark:text-amber-400' : ''">
                                {{ formatLimit(plan.max_products) }}
                            </span>
                        </div>
                    </div>

                    <!-- Features list -->
                    <ul v-if="plan.features?.length" class="mb-4 flex-1 space-y-1.5 text-sm">
                        <li v-for="(feature, i) in plan.features" :key="i" class="flex items-start gap-2">
                            <Check class="mt-0.5 h-3.5 w-3.5 shrink-0 text-green-500" />
                            <span>{{ feature }}</span>
                        </li>
                    </ul>

                    <!-- Action button -->
                    <div class="mt-auto">
                        <Button
                            v-if="isCurrentPlan(plan)"
                            variant="outline"
                            class="w-full"
                            disabled
                        >
                            Current Plan
                        </Button>
                        <Button
                            v-else-if="isUpgrade(plan)"
                            class="w-full bg-amber-500 text-white hover:bg-amber-600"
                        >
                            <Sparkles class="mr-2 h-4 w-4" />
                            Upgrade
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            class="w-full"
                            disabled
                        >
                            Downgrade
                        </Button>
                    </div>
                </div>
            </div>

            <p class="text-center text-xs text-muted-foreground">
                Payment integration coming soon. Contact support to upgrade your plan.
            </p>
        </DialogContent>
    </Dialog>
</template>
