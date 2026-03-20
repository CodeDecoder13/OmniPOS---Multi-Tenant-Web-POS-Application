<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTenant } from '@/composables/useTenant';
import type { Promotion } from '@/types';

const props = defineProps<{
    promotion: Promotion;
}>();

const { tenantUrl } = useTenant();

const form = useForm({
    code: props.promotion.code,
    name: props.promotion.name,
    type: props.promotion.type,
    value: Number(props.promotion.value),
    min_order_amount: props.promotion.min_order_amount ? Number(props.promotion.min_order_amount) : undefined,
    max_discount: props.promotion.max_discount ? Number(props.promotion.max_discount) : undefined,
    start_date: props.promotion.start_date?.split('T')[0] ?? '',
    end_date: props.promotion.end_date?.split('T')[0] ?? '',
    usage_limit: props.promotion.usage_limit ?? undefined,
    description: props.promotion.description ?? '',
    is_active: props.promotion.is_active,
});

function onTypeChange(value: string | number | boolean | Record<string, string>) {
    form.type = String(value) as Promotion['type'];
}

function submit() {
    form.transform((data) => ({
        ...data,
        code: data.code.toUpperCase(),
        start_date: data.start_date || null,
        end_date: data.end_date || null,
        min_order_amount: data.min_order_amount || null,
        max_discount: data.max_discount || null,
        usage_limit: data.usage_limit || null,
        description: data.description || null,
    })).put(tenantUrl(`promotions/${props.promotion.id}`));
}

const breadcrumbs = [
    { title: 'Promotions', href: tenantUrl('promotions') },
    { title: 'Edit', href: tenantUrl(`promotions/${props.promotion.id}/edit`) },
];
</script>

<template>
    <TenantLayout title="Edit Promotion" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Edit Promotion</h1>
                <p class="text-sm text-muted-foreground">Used {{ promotion.used_count }} times</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="code">Code *</Label>
                        <Input id="code" v-model="form.code" class="uppercase" :class="{ 'border-destructive': form.errors.code }" />
                        <p v-if="form.errors.code" class="text-sm text-destructive">{{ form.errors.code }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="name">Name *</Label>
                        <Input id="name" v-model="form.name" :class="{ 'border-destructive': form.errors.name }" />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="type">Type *</Label>
                        <Select :model-value="form.type" @update:model-value="onTypeChange">
                            <SelectTrigger :class="{ 'border-destructive': form.errors.type }">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="percentage">Percentage (%)</SelectItem>
                                <SelectItem value="fixed">Fixed Amount</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.type" class="text-sm text-destructive">{{ form.errors.type }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="value">Value *</Label>
                        <Input id="value" v-model.number="form.value" type="number" min="0" :step="form.type === 'percentage' ? 1 : 0.01" :class="{ 'border-destructive': form.errors.value }" />
                        <p v-if="form.errors.value" class="text-sm text-destructive">{{ form.errors.value }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="min_order_amount">Minimum Order Amount</Label>
                        <Input id="min_order_amount" v-model.number="form.min_order_amount" type="number" min="0" step="0.01" placeholder="No minimum" />
                        <p v-if="form.errors.min_order_amount" class="text-sm text-destructive">{{ form.errors.min_order_amount }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="max_discount">Maximum Discount</Label>
                        <Input id="max_discount" v-model.number="form.max_discount" type="number" min="0" step="0.01" placeholder="No cap" />
                        <p v-if="form.errors.max_discount" class="text-sm text-destructive">{{ form.errors.max_discount }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="start_date">Start Date</Label>
                        <Input id="start_date" v-model="form.start_date" type="date" />
                        <p v-if="form.errors.start_date" class="text-sm text-destructive">{{ form.errors.start_date }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="end_date">End Date</Label>
                        <Input id="end_date" v-model="form.end_date" type="date" />
                        <p v-if="form.errors.end_date" class="text-sm text-destructive">{{ form.errors.end_date }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="usage_limit">Usage Limit</Label>
                    <Input id="usage_limit" v-model.number="form.usage_limit" type="number" min="1" placeholder="Unlimited" />
                    <p v-if="form.errors.usage_limit" class="text-sm text-destructive">{{ form.errors.usage_limit }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea id="description" v-model="form.description" rows="2" />
                    <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <Switch id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                    <Label for="is_active">Active</Label>
                </div>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" type="button" as-child>
                        <a :href="tenantUrl('promotions')">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">Update Promotion</Button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
