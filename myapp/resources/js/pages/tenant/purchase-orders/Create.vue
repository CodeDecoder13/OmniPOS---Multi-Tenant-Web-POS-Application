<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTenant } from '@/composables/useTenant';
import type { Supplier, Branch, Product } from '@/types';

const props = defineProps<{
    suppliers: Pick<Supplier, 'id' | 'name'>[];
    branches: Pick<Branch, 'id' | 'name'>[];
    products: Pick<Product, 'id' | 'name' | 'sku' | 'cost_price'>[];
}>();

const { tenantUrl } = useTenant();

const form = useForm({
    supplier_id: '',
    branch_id: '',
    expected_date: '',
    notes: '',
    items: [{ product_id: '', quantity_ordered: 1, unit_cost: '' }] as { product_id: string; quantity_ordered: number; unit_cost: string }[],
});

function addItem() {
    form.items.push({ product_id: '', quantity_ordered: 1, unit_cost: '' });
}

function removeItem(index: number) {
    form.items.splice(index, 1);
}

function onProductSelect(index: number, productId: string | number | boolean | Record<string, string>) {
    const id = String(productId);
    form.items[index].product_id = id;
    const product = props.products.find(p => String(p.id) === id);
    if (product?.cost_price) {
        form.items[index].unit_cost = product.cost_price;
    }
}

const totalAmount = computed(() =>
    form.items.reduce((sum, item) => sum + (Number(item.unit_cost) || 0) * (item.quantity_ordered || 0), 0)
);

function submit() {
    form.post(tenantUrl('purchase-orders'));
}

const breadcrumbs = [
    { title: 'Purchase Orders', href: tenantUrl('purchase-orders') },
    { title: 'Create', href: tenantUrl('purchase-orders-create') },
];
</script>

<template>
    <TenantLayout title="Create Purchase Order" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl space-y-6">
            <h1 class="text-2xl font-bold">Create Purchase Order</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label>Supplier *</Label>
                        <Select v-model="form.supplier_id">
                            <SelectTrigger :class="{ 'border-destructive': form.errors.supplier_id }">
                                <SelectValue placeholder="Select supplier" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="s in suppliers" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.supplier_id" class="text-sm text-destructive">{{ form.errors.supplier_id }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label>Receiving Branch *</Label>
                        <Select v-model="form.branch_id">
                            <SelectTrigger :class="{ 'border-destructive': form.errors.branch_id }">
                                <SelectValue placeholder="Select branch" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.branch_id" class="text-sm text-destructive">{{ form.errors.branch_id }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label>Expected Date</Label>
                        <Input v-model="form.expected_date" type="date" />
                    </div>
                    <div class="space-y-2">
                        <Label>Notes</Label>
                        <Textarea v-model="form.notes" rows="1" />
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <Label class="text-base font-semibold">Items</Label>
                        <Button type="button" variant="outline" size="sm" @click="addItem">
                            <Plus class="mr-1 h-4 w-4" /> Add Item
                        </Button>
                    </div>
                    <p v-if="form.errors.items" class="text-sm text-destructive">{{ form.errors.items }}</p>

                    <div v-for="(item, index) in form.items" :key="index" class="flex items-start gap-3 rounded-md border p-3">
                        <div class="flex-1 space-y-2">
                            <Label>Product</Label>
                            <Select :model-value="item.product_id" @update:model-value="v => onProductSelect(index, v)">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select product" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="product in products" :key="product.id" :value="String(product.id)">
                                        {{ product.name }} {{ product.sku ? `(${product.sku})` : '' }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="w-24 space-y-2">
                            <Label>Qty</Label>
                            <Input v-model="item.quantity_ordered" type="number" min="1" />
                        </div>
                        <div class="w-32 space-y-2">
                            <Label>Unit Cost</Label>
                            <Input v-model="item.unit_cost" type="number" step="0.01" min="0" />
                        </div>
                        <Button type="button" variant="ghost" size="icon" class="mt-7" @click="removeItem(index)" :disabled="form.items.length <= 1">
                            <Trash2 class="h-4 w-4 text-destructive" />
                        </Button>
                    </div>

                    <div class="text-right text-sm font-semibold">
                        Total: {{ totalAmount.toFixed(2) }}
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" type="button" as-child>
                        <a :href="tenantUrl('purchase-orders')">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">Create PO</Button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
