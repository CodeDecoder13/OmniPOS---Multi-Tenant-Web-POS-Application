<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, FolderOpen, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Badge } from '@/components/ui/badge';
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

defineProps<{
    categories: Category[];
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Categories', href: tenantUrl('categories') },
];

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
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Categories</h1>
                <Button v-if="can('products.create')" as-child>
                    <Link :href="tenantUrl('categories/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Category
                    </Link>
                </Button>
            </div>

            <!-- Empty state -->
            <div
                v-if="categories.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border bg-white py-16 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="rounded-full bg-gray-100 p-4 dark:bg-gray-800">
                    <FolderOpen class="h-8 w-8 text-gray-400" />
                </div>
                <h3 class="mt-4 text-lg font-semibold">No categories yet</h3>
                <p class="mt-1 text-sm text-muted-foreground">Create your first category to organize products.</p>
                <Button v-if="can('products.create')" as-child class="mt-4">
                    <Link :href="tenantUrl('categories/create')">Create Category</Link>
                </Button>
            </div>

            <!-- Table -->
            <div v-else class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="hidden px-4 py-3 text-left font-medium md:table-cell">Slug</th>
                            <th class="px-4 py-3 text-left font-medium">Products</th>
                            <th class="px-4 py-3 text-left font-medium">Sort Order</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="category in categories"
                            :key="category.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3 font-medium">{{ category.name }}</td>
                            <td class="hidden px-4 py-3 font-mono text-xs md:table-cell">{{ category.slug }}</td>
                            <td class="px-4 py-3">{{ category.products_count ?? 0 }}</td>
                            <td class="px-4 py-3">{{ category.sort_order }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="category.is_active ? 'default' : 'secondary'">
                                    {{ category.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
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
                                        <Trash2 class="h-4 w-4 text-red-500" />
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
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteCategory" :disabled="deleting">
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
