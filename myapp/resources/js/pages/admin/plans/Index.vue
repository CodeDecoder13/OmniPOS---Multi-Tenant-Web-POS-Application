<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem, Plan } from '@/types';

defineProps<{
    plans: Plan[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Plans', href: '/admin/plans' },
];

const editingPlan = ref<Plan | null>(null);

const form = useForm({
    name: '',
    price: '',
    max_branches: undefined as number | undefined,
    max_users: undefined as number | undefined,
    max_products: undefined as number | undefined,
});

function startEdit(plan: Plan) {
    editingPlan.value = plan;
    form.name = plan.name;
    form.price = plan.price;
    form.max_branches = plan.max_branches ?? undefined;
    form.max_users = plan.max_users ?? undefined;
    form.max_products = plan.max_products ?? undefined;
}

function cancelEdit() {
    editingPlan.value = null;
    form.reset();
}

function saveEdit() {
    if (!editingPlan.value) return;
    form.patch(`/admin/plans/${editingPlan.value.id}`, {
        onSuccess: () => {
            editingPlan.value = null;
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Plans" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">Plans</h1>

            <div class="grid gap-4 md:grid-cols-3">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">{{ plan.name }}</h3>
                        <span class="text-sm text-muted-foreground">
                            {{ plan.subscriptions_count ?? 0 }} subscribers
                        </span>
                    </div>
                    <p class="mb-4 text-2xl font-bold text-teal-600">
                        ₱{{ Number(plan.price).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                        <span class="text-sm font-normal text-muted-foreground">/month</span>
                    </p>
                    <dl class="mb-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Max Branches</dt>
                            <dd>{{ plan.max_branches ?? 'Unlimited' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Max Users</dt>
                            <dd>{{ plan.max_users ?? 'Unlimited' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Max Products</dt>
                            <dd>{{ plan.max_products ?? 'Unlimited' }}</dd>
                        </div>
                    </dl>
                    <Button variant="outline" size="sm" class="w-full" @click="startEdit(plan)">
                        Edit Plan
                    </Button>
                </div>
            </div>

            <!-- Edit Dialog -->
            <div v-if="editingPlan" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="cancelEdit">
                <div class="w-full max-w-md rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Edit {{ editingPlan.name }}</h2>
                    <form @submit.prevent="saveEdit" class="flex flex-col gap-4">
                        <div class="grid gap-2">
                            <Label for="edit-name">Name</Label>
                            <Input id="edit-name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-price">Price (₱)</Label>
                            <Input id="edit-price" v-model="form.price" type="number" step="0.01" min="0" />
                            <InputError :message="form.errors.price" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-branches">Max Branches (empty = unlimited)</Label>
                            <Input id="edit-branches" v-model="form.max_branches" type="number" min="1" />
                            <InputError :message="form.errors.max_branches" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-users">Max Users (empty = unlimited)</Label>
                            <Input id="edit-users" v-model="form.max_users" type="number" min="1" />
                            <InputError :message="form.errors.max_users" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-products">Max Products (empty = unlimited)</Label>
                            <Input id="edit-products" v-model="form.max_products" type="number" min="1" />
                            <InputError :message="form.errors.max_products" />
                        </div>
                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="cancelEdit">Cancel</Button>
                            <Button type="submit" :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
                                Save Changes
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
