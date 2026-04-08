<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Search, Trash2 } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import type { Customer, PaginatedData } from '@/types';

const props = defineProps<{
    customers: PaginatedData<Customer>;
    filters: { search?: string };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const search = ref(props.filters.search ?? '');
const deleteDialog = ref(false);
const customerToDelete = ref<Customer | null>(null);

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(tenantUrl('customers'), { search: value || undefined }, { preserveState: true, replace: true });
    }, 300);
});

function confirmDelete(customer: Customer) {
    customerToDelete.value = customer;
    deleteDialog.value = true;
}

function deleteCustomer() {
    if (!customerToDelete.value) return;
    router.delete(tenantUrl(`customers/${customerToDelete.value.id}`), {
        onFinish: () => {
            deleteDialog.value = false;
            customerToDelete.value = null;
        },
    });
}

const breadcrumbs = [{ title: 'Customers', href: tenantUrl('customers') }];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold tracking-tight">Customers</h1>
                <Button v-if="can('orders.manage')" as-child>
                    <Link :href="tenantUrl('customers/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Customer
                    </Link>
                </Button>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative max-w-sm flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search customers..." class="pl-9" />
                </div>
            </div>

            <div class="rounded-md border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Email</th>
                            <th class="px-4 py-3 text-left font-medium">Phone</th>
                            <th class="px-4 py-3 text-center font-medium">Orders</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="customer in customers.data" :key="customer.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="tenantUrl(`customers/${customer.id}`)" class="text-primary hover:underline">{{ customer.name }}</Link>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ customer.email ?? '—' }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ customer.phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">{{ customer.orders_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button v-if="can('orders.manage')" variant="ghost" size="icon" as-child>
                                        <Link :href="tenantUrl(`customers/${customer.id}/edit`)">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button v-if="can('orders.manage')" variant="ghost" size="icon" @click="confirmDelete(customer)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No customers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="customers.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Showing {{ customers.from }} to {{ customers.to }} of {{ customers.total }} customers
                </p>
                <div class="flex gap-1">
                    <template v-for="link in customers.links" :key="link.label">
                        <Button
                            v-if="link.url"
                            variant="outline"
                            size="sm"
                            :class="{ 'bg-primary text-primary-foreground': link.active }"
                            as-child
                        >
                            <Link :href="link.url" v-html="link.label" />
                        </Button>
                        <Button v-else variant="outline" size="sm" disabled v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Customer</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ customerToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteCustomer">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
