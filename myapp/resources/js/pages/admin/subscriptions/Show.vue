<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem, TenantSubscription } from '@/types';
import { ref } from 'vue';

const props = defineProps<{
    subscription: TenantSubscription & { tenant?: { id: string; name: string; owner?: { name: string; email: string } } };
    plans: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Subscriptions', href: '/admin/subscriptions' },
    { title: props.subscription.tenant?.name ?? 'Detail', href: `/admin/subscriptions/${props.subscription.id}` },
];

const changePlanForm = useForm({
    plan_id: props.subscription.plan_id,
});

const extendDays = ref(14);

function changePlan() {
    changePlanForm.put(`/admin/subscriptions/${props.subscription.id}`);
}

function cancelSubscription() {
    if (!confirm('Cancel this subscription?')) return;
    router.patch(`/admin/subscriptions/${props.subscription.id}/cancel`);
}

function reactivateSubscription() {
    router.patch(`/admin/subscriptions/${props.subscription.id}/reactivate`);
}

function extendTrial() {
    router.patch(`/admin/subscriptions/${props.subscription.id}/extend-trial`, {
        days: extendDays.value,
    });
}

function statusColor(status: string): string {
    const colors: Record<string, string> = {
        active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        trial: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        expired: 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400',
        past_due: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    };
    return colors[status] ?? colors.expired;
}
</script>

<template>
    <Head :title="`Subscription: ${subscription.tenant?.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Subscription Management</h1>
                    <p class="text-muted-foreground">
                        <Link v-if="subscription.tenant" :href="`/admin/tenants/${subscription.tenant.id}`" class="text-teal-600 hover:underline">
                            {{ subscription.tenant.name }}
                        </Link>
                    </p>
                </div>
                <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium" :class="statusColor(subscription.status)">
                    {{ subscription.status }}
                </span>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Details -->
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Details</h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-muted-foreground">Current Plan</dt>
                            <dd class="font-medium">{{ subscription.plan?.name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Price</dt>
                            <dd>&#8369;{{ Number(subscription.plan?.price ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}/mo</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Trial Ends</dt>
                            <dd>{{ subscription.trial_ends_at ? new Date(subscription.trial_ends_at).toLocaleDateString() : 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Ends At</dt>
                            <dd>{{ subscription.ends_at ? new Date(subscription.ends_at).toLocaleDateString() : 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Created</dt>
                            <dd>{{ new Date(subscription.created_at).toLocaleDateString() }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-4">
                    <!-- Change Plan -->
                    <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="mb-4 text-lg font-semibold">Change Plan</h2>
                        <form @submit.prevent="changePlan" class="flex flex-col gap-3">
                            <select
                                v-model="changePlanForm.plan_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                            </select>
                            <Button type="submit" :disabled="changePlanForm.processing" class="bg-teal-600 hover:bg-teal-700">
                                Update Plan
                            </Button>
                        </form>
                    </div>

                    <!-- Extend Trial -->
                    <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="mb-4 text-lg font-semibold">Extend Trial</h2>
                        <div class="flex gap-3">
                            <Input v-model="extendDays" type="number" min="1" max="365" class="w-24" />
                            <span class="flex items-center text-sm text-muted-foreground">days</span>
                            <Button class="bg-teal-600 hover:bg-teal-700" @click="extendTrial">Extend</Button>
                        </div>
                    </div>

                    <!-- Cancel / Reactivate -->
                    <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="mb-4 text-lg font-semibold">Status Actions</h2>
                        <div class="flex gap-3">
                            <Button
                                v-if="subscription.status !== 'cancelled'"
                                variant="destructive"
                                @click="cancelSubscription"
                            >
                                Cancel Subscription
                            </Button>
                            <Button
                                v-if="subscription.status === 'cancelled'"
                                class="bg-teal-600 hover:bg-teal-700"
                                @click="reactivateSubscription"
                            >
                                Reactivate
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
