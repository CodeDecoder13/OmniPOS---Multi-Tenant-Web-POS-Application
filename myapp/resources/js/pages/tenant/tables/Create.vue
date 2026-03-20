<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTenant } from '@/composables/useTenant';
import type { Branch } from '@/types';

const props = defineProps<{
    branches: Pick<Branch, 'id' | 'name'>[];
}>();

const { tenantUrl } = useTenant();

const form = useForm({
    name: '',
    branch_id: null as number | null,
    capacity: 4,
    sort_order: 0,
    is_active: true,
});

function onBranchChange(value: string | number | boolean | Record<string, string>) {
    const v = String(value);
    form.branch_id = v === 'none' ? null : Number(v);
}

function submit() {
    form.post(tenantUrl('tables'));
}

const breadcrumbs = [
    { title: 'Tables', href: tenantUrl('tables') },
    { title: 'Create', href: tenantUrl('tables/create') },
];
</script>

<template>
    <TenantLayout title="Create Table" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl space-y-6">
            <h1 class="text-2xl font-bold">Create Table</h1>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="name">Name *</Label>
                    <Input id="name" v-model="form.name" placeholder="e.g. Table 1, Patio A" :class="{ 'border-destructive': form.errors.name }" />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="branch_id">Branch</Label>
                        <Select :model-value="form.branch_id ? String(form.branch_id) : 'none'" @update:model-value="onBranchChange">
                            <SelectTrigger>
                                <SelectValue placeholder="Select branch" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">All Branches</SelectItem>
                                <SelectItem v-for="branch in branches" :key="branch.id" :value="String(branch.id)">
                                    {{ branch.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.branch_id" class="text-sm text-destructive">{{ form.errors.branch_id }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="capacity">Capacity *</Label>
                        <Input id="capacity" v-model.number="form.capacity" type="number" min="1" max="100" :class="{ 'border-destructive': form.errors.capacity }" />
                        <p v-if="form.errors.capacity" class="text-sm text-destructive">{{ form.errors.capacity }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="sort_order">Sort Order</Label>
                    <Input id="sort_order" v-model.number="form.sort_order" type="number" min="0" />
                    <p v-if="form.errors.sort_order" class="text-sm text-destructive">{{ form.errors.sort_order }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <Switch id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                    <Label for="is_active">Active</Label>
                </div>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" type="button" as-child>
                        <a :href="tenantUrl('tables')">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">Create Table</Button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
