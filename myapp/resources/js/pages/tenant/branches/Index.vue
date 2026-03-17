<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from 'lucide-vue-next';
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
import type { BreadcrumbItem, Branch, PaginatedData } from '@/types';
import { useTenant } from '@/composables/useTenant';

const props = defineProps<{
    branches: PaginatedData<Branch>;
}>();

const { tenantUrl } = useTenant();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Branches', href: tenantUrl('branches') },
];

const deleteDialog = ref(false);
const branchToDelete = ref<Branch | null>(null);
const deleting = ref(false);

function confirmDelete(branch: Branch) {
    branchToDelete.value = branch;
    deleteDialog.value = true;
}

function deleteBranch() {
    if (!branchToDelete.value) return;
    deleting.value = true;
    router.delete(tenantUrl(`branches/${branchToDelete.value.id}`), {
        onFinish: () => {
            deleting.value = false;
            deleteDialog.value = false;
            branchToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Branches" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Branches</h1>
                <Button as-child>
                    <Link :href="tenantUrl('branches/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Branch
                    </Link>
                </Button>
            </div>

            <!-- Empty state -->
            <div
                v-if="branches.data.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border bg-white py-16 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="rounded-full bg-gray-100 p-4 dark:bg-gray-800">
                    <Plus class="h-8 w-8 text-gray-400" />
                </div>
                <h3 class="mt-4 text-lg font-semibold">No branches yet</h3>
                <p class="mt-1 text-sm text-muted-foreground">Create your first branch to get started.</p>
                <Button as-child class="mt-4">
                    <Link :href="tenantUrl('branches/create')">Create Branch</Link>
                </Button>
            </div>

            <!-- Table -->
            <div v-else class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Code</th>
                            <th class="hidden px-4 py-3 text-left font-medium md:table-cell">Email</th>
                            <th class="hidden px-4 py-3 text-left font-medium md:table-cell">Phone</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="branch in branches.data"
                            :key="branch.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3 font-medium">{{ branch.name }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ branch.code }}</td>
                            <td class="hidden px-4 py-3 md:table-cell">{{ branch.email || '—' }}</td>
                            <td class="hidden px-4 py-3 md:table-cell">{{ branch.phone || '—' }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="branch.is_active ? 'default' : 'secondary'">
                                    {{ branch.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="tenantUrl(`branches/${branch.id}/edit`)">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="confirmDelete(branch)">
                                        <Trash2 class="h-4 w-4 text-red-500" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="branches.last_page > 1" class="flex items-center justify-between border-t px-4 py-3 dark:border-gray-800">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ branches.from }} to {{ branches.to }} of {{ branches.total }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in branches.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="rounded-md px-3 py-1 text-sm"
                                :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Branch</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ branchToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteBranch" :disabled="deleting">
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
