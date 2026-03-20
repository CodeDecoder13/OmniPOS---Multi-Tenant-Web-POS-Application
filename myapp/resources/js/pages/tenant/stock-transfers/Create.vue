<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTenant } from '@/composables/useTenant';
import type { Branch, Product } from '@/types';

const props = defineProps<{
    branches: Pick<Branch, 'id' | 'name'>[];
    products: Pick<Product, 'id' | 'name' | 'sku'>[];
}>();

const { tenantUrl } = useTenant();

const form = useForm({
    source_branch_id: '',
    destination_branch_id: '',
    notes: '',
    items: [{ product_id: '', quantity_requested: 1 }] as { product_id: string; quantity_requested: number }[],
});

function addItem() {
    form.items.push({ product_id: '', quantity_requested: 1 });
}

function removeItem(index: number) {
    form.items.splice(index, 1);
}

function submit() {
    form.post(tenantUrl('stock-transfers'));
}

const breadcrumbs = [
    { title: 'Stock Transfers', href: tenantUrl('stock-transfers') },
    { title: 'Create', href: tenantUrl('stock-transfers-create') },
];
</script>

<template>
    <TenantLayout title="Create Stock Transfer" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl space-y-6">
            <h1 class="text-2xl font-bold">Create Stock Transfer</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label>Source Branch *</Label>
                        <Select v-model="form.source_branch_id">
                            <SelectTrigger :class="{ 'border-destructive': form.errors.source_branch_id }">
                                <SelectValue placeholder="Select source branch" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="branch in branches" :key="branch.id" :value="String(branch.id)">
                                    {{ branch.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.source_branch_id" class="text-sm text-destructive">{{ form.errors.source_branch_id }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label>Destination Branch *</Label>
                        <Select v-model="form.destination_branch_id">
                            <SelectTrigger :class="{ 'border-destructive': form.errors.destination_branch_id }">
                                <SelectValue placeholder="Select destination branch" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="branch in branches" :key="branch.id" :value="String(branch.id)">
                                    {{ branch.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.destination_branch_id" class="text-sm text-destructive">{{ form.errors.destination_branch_id }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label>Notes</Label>
                    <Textarea v-model="form.notes" rows="2" />
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
                            <Select v-model="item.product_id">
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
                        <div class="w-32 space-y-2">
                            <Label>Quantity</Label>
                            <Input v-model="item.quantity_requested" type="number" min="1" />
                        </div>
                        <Button type="button" variant="ghost" size="icon" class="mt-7" @click="removeItem(index)" :disabled="form.items.length <= 1">
                            <Trash2 class="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" type="button" as-child>
                        <a :href="tenantUrl('stock-transfers')">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">Create Transfer</Button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
