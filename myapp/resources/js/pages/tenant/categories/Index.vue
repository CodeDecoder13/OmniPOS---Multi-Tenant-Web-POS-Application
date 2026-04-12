<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle, Edit, FolderOpen, Package, Plus, Trash2, XCircle } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { BreadcrumbItem, Category } from '@/types';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps<{
    categories: Category[];
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Categories', href: tenantUrl('categories') },
];

// Stats
const stats = computed(() => {
    const data = props.categories;
    const active = data.filter(c => c.is_active).length;
    const totalProducts = data.reduce((sum, c) => sum + (c.products_count ?? 0), 0);
    return { total: data.length, active, inactive: data.length - active, totalProducts };
});

// Delete dialog
const deleteDialog = ref(false);
const categoryToDelete = ref<Category | null>(null);
const deleting = ref(false);

function confirmDelete(category: Category) {
    categoryToDelete.value = category;
    deleteDialog.value = true;
}

function deleteCategory() {
    if (!categoryToDelete.value) return;
    deleting.value = true;
    router.delete(tenantUrl(`categories/${categoryToDelete.value.id}`), {
        onFinish: () => {
            deleting.value = false;
            deleteDialog.value = false;
            categoryToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Categories" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-green-600 text-white shadow-md">
                        <FolderOpen class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Categories</h1>
                        <p class="text-sm text-muted-foreground">Organize your products into categories</p>
                    </div>
                </div>
                <Button v-if="can('products.create')" class="w-full sm:w-auto" as-child>
                    <Link :href="tenantUrl('categories/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Category
                    </Link>
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                        <FolderOpen class="h-4.5 w-4.5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground">Total Categories</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                        <CheckCircle class="h-4.5 w-4.5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.active }}</p>
                        <p class="text-xs text-muted-foreground">Active</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-900/40">
                        <XCircle class="h-4.5 w-4.5 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.inactive }}</p>
                        <p class="text-xs text-muted-foreground">Inactive</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/40">
                        <Package class="h-4.5 w-4.5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.totalProducts }}</p>
                        <p class="text-xs text-muted-foreground">Total Products</p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="categories.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border bg-card py-16"
            >
                <div class="rounded-full bg-muted p-4">
                    <FolderOpen class="h-8 w-8 text-muted-foreground" />
                </div>
                <h3 class="mt-4 text-lg font-semibold">No categories yet</h3>
                <p class="mt-1 text-sm text-muted-foreground">Create your first category to organize products.</p>
                <Button v-if="can('products.create')" as-child class="mt-4">
                    <Link :href="tenantUrl('categories/create')">Create Category</Link>
                </Button>
            </div>

            <!-- Table -->
            <div v-else class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[500px] text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-3 py-3 text-left font-medium sm:px-4">Name</th>
                            <th class="hidden px-3 py-3 text-left font-medium md:table-cell sm:px-4">Slug</th>
                            <th class="px-3 py-3 text-center font-medium sm:px-4">Products</th>
                            <th class="hidden px-3 py-3 text-center font-medium sm:table-cell sm:px-4">Order</th>
                            <th class="px-3 py-3 text-center font-medium sm:px-4">Status</th>
                            <th class="px-3 py-3 text-right font-medium sm:px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="category in categories"
                            :key="category.id"
                            class="border-b transition-colors last:border-0 hover:bg-muted/30"
                        >
                            <!-- Name + slug on mobile -->
                            <td class="px-3 py-3 sm:px-4">
                                <p class="font-medium">{{ category.name }}</p>
                                <p class="text-xs text-muted-foreground md:hidden">{{ category.slug }}</p>
                            </td>
                            <!-- Slug -->
                            <td class="hidden px-3 py-3 font-mono text-xs md:table-cell sm:px-4">{{ category.slug }}</td>
                            <!-- Products count -->
                            <td class="px-3 py-3 text-center tabular-nums sm:px-4">{{ category.products_count ?? 0 }}</td>
                            <!-- Sort order -->
                            <td class="hidden px-3 py-3 text-center tabular-nums sm:table-cell sm:px-4">{{ category.sort_order }}</td>
                            <!-- Status -->
                            <td class="px-3 py-3 text-center sm:px-4">
                                <span
                                    :class="category.is_active
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800'
                                        : 'bg-gray-50 text-gray-600 ring-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-700'"
                                    class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                >
                                    <span
                                        :class="category.is_active ? 'bg-emerald-500' : 'bg-gray-400'"
                                        class="h-1.5 w-1.5 rounded-full"
                                    />
                                    {{ category.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <!-- Actions -->
                            <td class="px-3 py-3 text-right sm:px-4">
                                <div class="flex items-center justify-end gap-1">
                                    <Button v-if="can('products.edit')" variant="ghost" size="icon" as-child>
                                        <Link :href="tenantUrl(`categories/${category.id}/edit`)">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="can('products.delete')"
                                        variant="ghost"
                                        size="icon"
                                        @click="confirmDelete(category)"
                                    >
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Category</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ categoryToDelete?.name }}"? This action cannot be undone.
                        Categories with products cannot be deleted.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteCategory" :disabled="deleting">
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
