<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, Plan } from '@/types';
import { Plus } from 'lucide-vue-next';

defineProps<{
    plans: Plan[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Plans', href: '/admin/plans' },
];

function togglePlan(id: number) {
    router.patch(`/admin/plans/${id}/toggle`);
}

function deletePlan(id: number) {
    if (!confirm('Are you sure? Plans with active subscriptions cannot be deleted.')) return;
    router.delete(`/admin/plans/${id}`);
}
</script>

<template>
    <Head title="Plans" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Plans</h1>
                <Button as-child class="bg-teal-600 hover:bg-teal-700">
                    <Link href="/admin/plans/create">
                        <Plus class="mr-1 size-4" />
                        Create Plan
                    </Link>
                </Button>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900"
                    :class="{ 'opacity-60': !plan.is_active }"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">{{ plan.name }}</h3>
                        <span
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="plan.is_active
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                        >
                            {{ plan.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="mb-4 text-2xl font-bold text-teal-600">
                        &#8369;{{ Number(plan.price).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
                        <span class="text-sm font-normal text-muted-foreground">/month</span>
                    </p>
                    <dl class="mb-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Subscribers</dt>
                            <dd class="font-medium">{{ plan.subscriptions_count ?? 0 }}</dd>
                        </div>
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
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" class="flex-1" as-child>
                            <Link :href="`/admin/plans/${plan.id}/edit`">Edit</Link>
                        </Button>
                        <Button
                            size="sm"
                            :variant="plan.is_active ? 'destructive' : 'default'"
                            class="flex-1"
                            @click="togglePlan(plan.id)"
                        >
                            {{ plan.is_active ? 'Deactivate' : 'Activate' }}
                        </Button>
                        <Button size="sm" variant="destructive" @click="deletePlan(plan.id)">
                            Delete
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
