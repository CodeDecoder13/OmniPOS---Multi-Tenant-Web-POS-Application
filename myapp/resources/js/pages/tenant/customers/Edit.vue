<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTenant } from '@/composables/useTenant';
import type { Customer } from '@/types';

const props = defineProps<{
    customer: Customer;
}>();

const { tenantUrl } = useTenant();

const form = useForm({
    _method: 'PUT' as const,
    name: props.customer.name,
    email: props.customer.email ?? '',
    phone: props.customer.phone ?? '',
    address: props.customer.address ?? '',
    notes: props.customer.notes ?? '',
});

function submit() {
    form.post(tenantUrl(`customers/${props.customer.id}`));
}

const breadcrumbs = [
    { title: 'Customers', href: tenantUrl('customers') },
    { title: 'Edit', href: tenantUrl(`customers/${props.customer.id}/edit`) },
];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl space-y-6 p-6">
            <h1 class="text-2xl font-bold tracking-tight">Edit Customer</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-lg border p-6 space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Name <span class="text-destructive">*</span></Label>
                        <Input id="name" v-model="form.name" :class="{ 'border-destructive': form.errors.name }" />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" type="email" v-model="form.email" :class="{ 'border-destructive': form.errors.email }" />
                            <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="phone">Phone</Label>
                            <Input id="phone" v-model="form.phone" :class="{ 'border-destructive': form.errors.phone }" />
                            <p v-if="form.errors.phone" class="text-sm text-destructive">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="address">Address</Label>
                        <Textarea id="address" v-model="form.address" rows="2" :class="{ 'border-destructive': form.errors.address }" />
                        <p v-if="form.errors.address" class="text-sm text-destructive">{{ form.errors.address }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Notes</Label>
                        <Textarea id="notes" v-model="form.notes" rows="2" :class="{ 'border-destructive': form.errors.notes }" />
                        <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Button variant="outline" type="button" as-child>
                        <a :href="tenantUrl('customers')">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Update Customer' }}
                    </Button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
