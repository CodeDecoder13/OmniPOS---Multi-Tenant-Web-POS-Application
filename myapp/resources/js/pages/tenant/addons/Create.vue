<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useTenant } from '@/composables/useTenant';

const { tenantUrl } = useTenant();

const form = useForm({
    name: '',
    price: '',
    category_label: '',
    is_active: true,
    sort_order: 0,
});

function submit() {
    form.post(tenantUrl('addons'));
}

const breadcrumbs = [
    { title: 'Add-ons', href: tenantUrl('addons') },
    { title: 'Create', href: tenantUrl('addons/create') },
];
</script>

<template>
    <TenantLayout title="Create Add-on" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-lg space-y-6">
            <h1 class="text-2xl font-bold">Create Add-on</h1>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="name">Name *</Label>
                    <Input id="name" v-model="form.name" :class="{ 'border-destructive': form.errors.name }" />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="price">Price *</Label>
                        <Input id="price" v-model="form.price" type="number" step="0.01" min="0" />
                        <p v-if="form.errors.price" class="text-sm text-destructive">{{ form.errors.price }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="sort_order">Sort Order</Label>
                        <Input id="sort_order" v-model="form.sort_order" type="number" min="0" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="category_label">Category Label</Label>
                    <Input id="category_label" v-model="form.category_label" placeholder="e.g. Toppings, Extras" />
                </div>

                <div class="flex items-center gap-2">
                    <Switch id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                    <Label for="is_active">Active</Label>
                </div>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" type="button" as-child>
                        <a :href="tenantUrl('addons')">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">Create Add-on</Button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
