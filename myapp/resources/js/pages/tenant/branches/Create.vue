<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';
import { useTenant } from '@/composables/useTenant';

const { tenantUrl } = useTenant();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Branches', href: tenantUrl('branches') },
    { title: 'Create', href: tenantUrl('branches/create') },
];

const form = useForm({
    name: '',
    code: '',
    address: '',
    phone: '',
    email: '',
});

function submit() {
    form.post(tenantUrl('branches'));
}
</script>

<template>
    <Head title="Create Branch" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <h1 class="mb-6 text-2xl font-bold">Create Branch</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="space-y-4">
                        <div>
                            <Label for="name">Branch Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Main Branch"
                                class="mt-1"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <Label for="code">Branch Code</Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="MAIN"
                                maxlength="20"
                                class="mt-1"
                            />
                            <p v-if="form.errors.code" class="mt-1 text-sm text-red-500">{{ form.errors.code }}</p>
                        </div>

                        <div>
                            <Label for="address">Address</Label>
                            <Input
                                id="address"
                                v-model="form.address"
                                placeholder="123 Main St"
                                class="mt-1"
                            />
                            <p v-if="form.errors.address" class="mt-1 text-sm text-red-500">{{ form.errors.address }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label for="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    placeholder="+63 912 345 6789"
                                    maxlength="20"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">{{ form.errors.phone }}</p>
                            </div>

                            <div>
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="branch@example.com"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">{{ form.errors.email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" type="button" as-child>
                        <a :href="tenantUrl('branches')">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Branch' }}
                    </Button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
