<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, PaginatedData, PromoCode } from '@/types';
import { Plus } from 'lucide-vue-next';

defineProps<{
    promoCodes: PaginatedData<PromoCode>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Promo Codes', href: '/admin/promo-codes' },
];

function deletePromoCode(id: number) {
    if (!confirm('Are you sure you want to delete this promo code?')) return;
    router.delete(`/admin/promo-codes/${id}`);
}

function formatDiscount(code: PromoCode): string {
    if (code.discount_type === 'percentage') {
        return `${Number(code.discount_value)}%`;
    }
    return `₱${Number(code.discount_value).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
}

function formatDate(date: string | null): string {
    if (!date) return '—';
    return new Date(date).toLocaleDateString();
}
</script>

<template>
    <Head title="Promo Codes" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Promo Codes</h1>
                <Button as-child class="bg-teal-600 hover:bg-teal-700">
                    <Link href="/admin/promo-codes/create">
                        <Plus class="mr-1 size-4" />
                        Create Promo Code
                    </Link>
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Code</th>
                            <th class="px-4 py-3 text-left font-medium">Discount</th>
                            <th class="px-4 py-3 text-left font-medium">Usage</th>
                            <th class="px-4 py-3 text-left font-medium">Valid Period</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="code in promoCodes.data"
                            :key="code.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3">
                                <span class="font-mono font-semibold">{{ code.code }}</span>
                                <div v-if="code.description" class="text-xs text-muted-foreground">{{ code.description }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ formatDiscount(code) }} {{ code.discount_type === 'percentage' ? 'off' : 'off' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                {{ code.used_count }}{{ code.max_uses ? ` / ${code.max_uses}` : '' }}
                                <span class="text-xs text-muted-foreground ml-1">({{ code.subscriptions_count ?? 0 }} subs)</span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground text-xs">
                                {{ formatDate(code.valid_from) }} — {{ formatDate(code.valid_until) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="code.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                >
                                    {{ code.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button size="sm" variant="outline" as-child>
                                        <Link :href="`/admin/promo-codes/${code.id}/edit`">Edit</Link>
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deletePromoCode(code.id)">
                                        Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="promoCodes.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                No promo codes found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="promoCodes.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in promoCodes.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg px-3 py-1.5 text-sm"
                        :class="link.active
                            ? 'bg-teal-600 text-white'
                            : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-lg px-3 py-1.5 text-sm text-muted-foreground"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
