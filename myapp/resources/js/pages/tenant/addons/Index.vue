<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Search, Trash2 } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Pagination from '@/components/Pagination.vue';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import type { Addon, PaginatedData } from '@/types';

const props = defineProps<{
    addons: PaginatedData<Addon>;
    filters: { search?: string; is_active?: string };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const search = ref(props.filters.search ?? '');
const deleteDialog = ref(false);
const addonToDelete = ref<Addon | null>(null);

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(tenantUrl('addons'), { search: value || undefined }, { preserveState: true, replace: true });
    }, 300);
});

function confirmDelete(addon: Addon) {
    addonToDelete.value = addon;
    deleteDialog.value = true;
}

function deleteAddon() {
    if (!addonToDelete.value) return;
    router.delete(tenantUrl(`addons/${addonToDelete.value.id}`), {
        onFinish: () => {
            deleteDialog.value = false;
            addonToDelete.value = null;
        },
    });
}

const breadcrumbs = [{ title: 'Add-ons', href: tenantUrl('addons') }];
</script>

<template>
    <TenantLayout title="Add-ons" :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Add-ons</h1>
                <Button v-if="can('products.create')" as-child>
                    <Link :href="tenantUrl('addons/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Add-on
                    </Link>
                </Button>
            </div>

            <div class="relative w-full max-w-sm">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search add-ons..." class="pl-9" />
            </div>

            <div class="rounded-md border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Category</th>
                            <th class="px-4 py-3 text-right font-medium">Price</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="addon in addons.data" :key="addon.id" class="border-b">
                            <td class="px-4 py-3 font-medium">{{ addon.name }}</td>
                            <td class="px-4 py-3">{{ addon.category_label ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ Number(addon.price).toFixed(2) }}</td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="addon.is_active ? 'default' : 'secondary'">
                                    {{ addon.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button v-if="can('products.edit')" variant="ghost" size="icon" as-child>
                                        <Link :href="tenantUrl(`addons/${addon.id}/edit`)">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button v-if="can('products.delete')" variant="ghost" size="icon" @click="confirmDelete(addon)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="addons.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No add-ons found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :data="addons" />
        </div>

        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Add-on</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ addonToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteAddon">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
