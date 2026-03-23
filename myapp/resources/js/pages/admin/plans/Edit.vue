<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem, Plan } from '@/types';
import { ref } from 'vue';

const props = defineProps<{
    plan: Plan;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Plans', href: '/admin/plans' },
    { title: 'Edit', href: `/admin/plans/${props.plan.id}/edit` },
];

const form = useForm({
    name: props.plan.name,
    price: props.plan.price,
    max_branches: props.plan.max_branches ?? undefined,
    max_users: props.plan.max_users ?? undefined,
    max_products: props.plan.max_products ?? undefined,
    features: props.plan.features ?? [],
    is_active: props.plan.is_active,
});

const newFeature = ref('');

function addFeature() {
    if (newFeature.value.trim()) {
        form.features.push(newFeature.value.trim());
        newFeature.value = '';
    }
}

function removeFeature(index: number) {
    form.features.splice(index, 1);
}

function submit() {
    form.transform((data) => ({
        ...data,
        max_branches: data.max_branches || null,
        max_users: data.max_users || null,
        max_products: data.max_products || null,
        features: data.features.length > 0 ? data.features : null,
    })).put(`/admin/plans/${props.plan.id}`);
}
</script>

<template>
    <Head :title="`Edit Plan: ${plan.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">Edit Plan: {{ plan.name }}</h1>

            <div class="mx-auto w-full max-w-2xl rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <form @submit.prevent="submit" class="flex flex-col gap-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">Plan Name</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="price">Monthly Price (&#8369;)</Label>
                            <Input id="price" v-model="form.price" type="number" step="0.01" min="0" />
                            <InputError :message="form.errors.price" />
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="max_branches">Max Branches</Label>
                            <Input id="max_branches" v-model="form.max_branches" type="number" min="1" placeholder="Unlimited" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="max_users">Max Users</Label>
                            <Input id="max_users" v-model="form.max_users" type="number" min="1" placeholder="Unlimited" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="max_products">Max Products</Label>
                            <Input id="max_products" v-model="form.max_products" type="number" min="1" placeholder="Unlimited" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label>Features</Label>
                        <div class="flex gap-2">
                            <Input v-model="newFeature" placeholder="Add a feature..." @keydown.enter.prevent="addFeature" />
                            <Button type="button" variant="outline" @click="addFeature">Add</Button>
                        </div>
                        <ul v-if="form.features.length > 0" class="mt-2 space-y-1">
                            <li v-for="(feature, index) in form.features" :key="index" class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-1.5 text-sm dark:bg-gray-800">
                                <span>{{ feature }}</span>
                                <button type="button" class="text-red-500 hover:text-red-700" @click="removeFeature(index)">&times;</button>
                            </li>
                        </ul>
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
                            <Link href="/admin/plans">Cancel</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
                            Update Plan
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
