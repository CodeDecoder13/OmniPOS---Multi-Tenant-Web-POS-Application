<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { CheckCircle, Edit, Mail, Phone, Plus, Search, ShoppingBag, Trash2, Users } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Pagination from '@/components/Pagination.vue';
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

// Stats
const stats = computed(() => {
    const data = props.customers.data;
    const withEmail = data.filter(c => c.email).length;
    const withPhone = data.filter(c => c.phone).length;
    const totalOrders = data.reduce((sum, c) => sum + (c.orders_count ?? 0), 0);
    return { total: data.length, withEmail, withPhone, totalOrders };
});

// Create customer dialog
const createDialog = ref(false);
const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    notes: '',
});

function openCreateDialog() {
    form.reset();
    form.clearErrors();
    createDialog.value = true;
}

function submitCreate() {
    form.post(tenantUrl('customers'), {
        onSuccess: () => {
            createDialog.value = false;
        },
    });
}

// Delete
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
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 text-white shadow-md">
                        <Users class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Customers</h1>
                        <p class="text-sm text-muted-foreground">Manage your customer database</p>
                    </div>
                </div>
                <Button v-if="can('orders.manage')" class="w-full sm:w-auto" @click="openCreateDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Customer
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                        <Users class="h-4.5 w-4.5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground">Total Customers</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                        <Mail class="h-4.5 w-4.5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.withEmail }}</p>
                        <p class="text-xs text-muted-foreground">With Email</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/40">
                        <Phone class="h-4.5 w-4.5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.withPhone }}</p>
                        <p class="text-xs text-muted-foreground">With Phone</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/40">
                        <ShoppingBag class="h-4.5 w-4.5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.totalOrders }}</p>
                        <p class="text-xs text-muted-foreground">Total Orders</p>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="rounded-xl border bg-card p-3 sm:p-4">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search customers..." class="pl-9" />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[500px] text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-3 py-3 text-left font-medium sm:px-4">Name</th>
                            <th class="hidden px-3 py-3 text-left font-medium sm:table-cell sm:px-4">Email</th>
                            <th class="hidden px-3 py-3 text-left font-medium sm:table-cell sm:px-4">Phone</th>
                            <th class="px-3 py-3 text-center font-medium sm:px-4">Orders</th>
                            <th class="px-3 py-3 text-right font-medium sm:px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="customer in customers.data"
                            :key="customer.id"
                            class="border-b transition-colors last:border-0 hover:bg-muted/30"
                        >
                            <!-- Name + email/phone on mobile -->
                            <td class="px-3 py-3 sm:px-4">
                                <Link :href="tenantUrl(`customers/${customer.id}`)" class="font-medium text-primary hover:underline">
                                    {{ customer.name }}
                                </Link>
                                <div class="mt-0.5 space-y-0.5 sm:hidden">
                                    <p v-if="customer.email" class="text-xs text-muted-foreground">{{ customer.email }}</p>
                                    <p v-if="customer.phone" class="text-xs text-muted-foreground">{{ customer.phone }}</p>
                                </div>
                            </td>
                            <!-- Email -->
                            <td class="hidden px-3 py-3 text-muted-foreground sm:table-cell sm:px-4">
                                {{ customer.email ?? '—' }}
                            </td>
                            <!-- Phone -->
                            <td class="hidden px-3 py-3 text-muted-foreground sm:table-cell sm:px-4">
                                {{ customer.phone ?? '—' }}
                            </td>
                            <!-- Orders count -->
                            <td class="px-3 py-3 text-center tabular-nums sm:px-4">
                                {{ customer.orders_count ?? 0 }}
                            </td>
                            <!-- Actions -->
                            <td class="px-3 py-3 text-right sm:px-4">
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
                            <td colspan="5" class="px-4 py-12 text-center text-muted-foreground">
                                <Users class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                No customers found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :data="customers" />
        </div>

        <!-- Create Customer Dialog -->
        <Dialog v-model:open="createDialog">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Create Customer</DialogTitle>
                    <DialogDescription>
                        Fill in the details to add a new customer to your database.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="create-name">Name *</Label>
                        <Input
                            id="create-name"
                            v-model="form.name"
                            placeholder="Customer name"
                            :class="{ 'border-destructive': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="create-email">Email</Label>
                            <Input
                                id="create-email"
                                v-model="form.email"
                                type="email"
                                placeholder="email@example.com"
                                :class="{ 'border-destructive': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="create-phone">Phone</Label>
                            <Input
                                id="create-phone"
                                v-model="form.phone"
                                placeholder="+1 234 567 890"
                                :class="{ 'border-destructive': form.errors.phone }"
                            />
                            <p v-if="form.errors.phone" class="text-sm text-destructive">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-address">Address</Label>
                        <Textarea
                            id="create-address"
                            v-model="form.address"
                            placeholder="Street address, city, etc."
                            rows="2"
                            :class="{ 'border-destructive': form.errors.address }"
                        />
                        <p v-if="form.errors.address" class="text-sm text-destructive">{{ form.errors.address }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-notes">Notes</Label>
                        <Textarea
                            id="create-notes"
                            v-model="form.notes"
                            placeholder="Any additional notes about this customer..."
                            rows="2"
                            :class="{ 'border-destructive': form.errors.notes }"
                        />
                        <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button variant="outline" type="button" @click="createDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Creating...' : 'Create Customer' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Customer</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ customerToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteCustomer">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
