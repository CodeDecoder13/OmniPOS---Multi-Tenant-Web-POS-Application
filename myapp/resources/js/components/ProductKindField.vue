<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InfoTip from '@/components/InfoTip.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/**
 * What kind of thing is this product?
 *   goods     physical stock that is counted
 *   service   something you do (a haircut, a repair): no stock, optional duration
 *   prepared  made to order (a drink, a dish): no stock
 *
 * Stored as two flags (is_food, is_service); this field keeps them from ever both being true.
 */
const isFood = defineModel<boolean>('isFood', { default: false });
const isService = defineModel<boolean>('isService', { default: false });
const duration = defineModel<number | null>('duration', { default: null });

const page = usePage();
const serviceWord = computed(() => (page.props.businessProfile as { terms?: { service?: string } } | null)?.terms?.service ?? 'Service');

type Kind = 'goods' | 'service' | 'prepared';
const kind = computed<Kind>({
    get: () => (isService.value ? 'service' : isFood.value ? 'prepared' : 'goods'),
    set: (k) => {
        isService.value = k === 'service';
        isFood.value = k === 'prepared';
        if (k !== 'service') duration.value = null;
    },
});

const options = computed<{ value: Kind; label: string; hint: string }[]>(() => [
    { value: 'goods', label: 'Goods', hint: 'Physical items you count in stock' },
    { value: 'service', label: serviceWord.value, hint: 'Something you do. No stock to count' },
    { value: 'prepared', label: 'Made to order', hint: 'Drinks and dishes. No stock to count' },
]);
</script>

<template>
    <div>
        <div class="flex items-center gap-1">
            <Label>Product Type</Label>
            <InfoTip text="Goods have a stock count. Services and made-to-order items do not, so they never show up in Inventory or run out." />
        </div>
        <div class="mt-2 grid gap-2 sm:grid-cols-3" role="radiogroup" aria-label="Product type">
            <button
                v-for="o in options"
                :key="o.value"
                type="button"
                role="radio"
                :aria-checked="kind === o.value"
                class="rounded-lg border p-3 text-left outline-none transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :class="kind === o.value ? 'border-primary bg-primary/5' : 'hover:bg-muted/50'"
                @click="kind = o.value"
            >
                <span class="block text-sm font-medium">{{ o.label }}</span>
                <span class="mt-0.5 block text-xs text-muted-foreground">{{ o.hint }}</span>
            </button>
        </div>

        <div v-if="kind === 'service'" class="mt-3">
            <div class="flex items-center gap-1">
                <Label for="duration_minutes">Duration (minutes)</Label>
                <InfoTip text="How long it usually takes. Optional. Used for appointments." />
            </div>
            <Input
                id="duration_minutes"
                :model-value="duration ?? undefined"
                @update:model-value="(v) => (duration = v === '' || v == null ? null : Number(v))"
                type="number"
                min="5"
                max="1440"
                step="5"
                placeholder="e.g. 60"
                class="mt-1 w-40"
            />
        </div>
    </div>
</template>
