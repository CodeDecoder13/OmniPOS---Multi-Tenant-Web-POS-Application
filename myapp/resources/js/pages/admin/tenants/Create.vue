<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    users: { id: number; name: string; email: string }[];
    plans: { id: number; name: string }[];
    businessTypes: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Tenants', href: '/admin/tenants' },
    { title: 'Create', href: '/admin/tenants/create' },
];

const form = useForm({
    name: '',
    business_type: '',
    owner_id: '' as string | number,
    plan_id: '' as string | number,
    is_active: true,
});

function submit() {
    form.post('/admin/tenants');
}
</script>

<template>
    <Head title="Create Tenant" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Create Tenant</h1>
            </div>

            <div class="mx-auto w-full max-w-2xl rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <form @submit.prevent="submit" class="flex flex-col gap-5">
                    <div class="grid gap-2">
                        <Label for="name">Store Name</Label>
                        <Input id="name" v-model="form.name" placeholder="e.g. My Coffee Shop" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="business_type">Business Type</Label>
                            <select
                                id="business_type"
                                v-model="form.business_type"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="" disabled>Select type...</option>
                                <option v-for="bt in businessTypes" :key="bt.value" :value="bt.value">
                                    {{ bt.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.business_type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="owner_id">Owner</Label>
                            <select
                                id="owner_id"
                                v-model="form.owner_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="" disabled>Select owner...</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                            <InputError :message="form.errors.owner_id" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="plan_id">Plan</Label>
                        <select
                            id="plan_id"
                            v-model="form.plan_id"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="" disabled>Select plan...</option>
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                {{ plan.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.plan_id" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                        />
                        <Label for="is_active" class="cursor-pointer">Active</Label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <Button type="button" variant="outline" as-child>
                            <Link href="/admin/tenants">Cancel</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
                            Create Tenant
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
