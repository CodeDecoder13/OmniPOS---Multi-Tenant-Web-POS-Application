<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { useTenant } from '@/composables/useTenant';
import type { Supplier } from '@/types';

const props = defineProps<{
    supplier: Supplier;
}>();

const { tenantUrl } = useTenant();

const form = useForm({
    name: props.supplier.name,
    contact_person: props.supplier.contact_person ?? '',
    email: props.supplier.email ?? '',
    phone: props.supplier.phone ?? '',
    address: props.supplier.address ?? '',
    notes: props.supplier.notes ?? '',
    is_active: props.supplier.is_active,
});

function submit() {
    form.put(tenantUrl(`suppliers/${props.supplier.id}`));
}

const breadcrumbs = [
    { title: 'Suppliers', href: tenantUrl('suppliers') },
    { title: 'Edit', href: tenantUrl(`suppliers/${props.supplier.id}/edit`) },
];
</script>

<template>
    <TenantLayout title="Edit Supplier" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl space-y-6">
            <h1 class="text-2xl font-bold">Edit Supplier</h1>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="name">Name *</Label>
                    <Input id="name" v-model="form.name" :class="{ 'border-destructive': form.errors.name }" />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="contact_person">Contact Person</Label>
                        <Input id="contact_person" v-model="form.contact_person" />
                        <p v-if="form.errors.contact_person" class="text-sm text-destructive">{{ form.errors.contact_person }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" />
                        <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="phone">Phone</Label>
                    <Input id="phone" v-model="form.phone" />
                    <p v-if="form.errors.phone" class="text-sm text-destructive">{{ form.errors.phone }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="address">Address</Label>
                    <Textarea id="address" v-model="form.address" rows="2" />
                    <p v-if="form.errors.address" class="text-sm text-destructive">{{ form.errors.address }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="notes">Notes</Label>
                    <Textarea id="notes" v-model="form.notes" rows="3" />
                    <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <Switch id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                    <Label for="is_active">Active</Label>
                </div>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" type="button" as-child>
                        <a :href="tenantUrl('suppliers')">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">Update Supplier</Button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
