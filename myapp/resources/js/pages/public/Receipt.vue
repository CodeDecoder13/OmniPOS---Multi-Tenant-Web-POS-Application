<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface ReceiptOrder {
    order_number: string;
    order_type: string;
    subtotal: string;
    discount_amount: string;
    tax_amount: string;
    total: string;
    status: string;
    created_at: string;
    customer: { name: string } | null;
    items: {
        product_name: string;
        product_price: string;
        quantity: number;
        subtotal: string;
    }[];
    payments: {
        method: string;
        amount: string;
    }[];
}

interface Store {
    name: string;
    address: string | null;
    phone: string | null;
}

const props = defineProps<{
    order: ReceiptOrder;
    store: Store;
}>();

function formatCurrency(value: string | number) {
    return '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function paymentMethodLabel(method: string) {
    const labels: Record<string, string> = {
        cash: 'Cash', card: 'Card', e_wallet: 'E-Wallet', bank_transfer: 'Bank Transfer', other: 'Other',
    };
    return labels[method] ?? method;
}
</script>

<template>
    <Head :title="`Receipt - ${order.order_number}`" />

    <div class="min-h-screen bg-gray-50 py-8 px-4">
        <div class="mx-auto max-w-md">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <!-- Store Header -->
                <div class="text-center mb-4">
                    <h1 class="text-xl font-bold">{{ store.name }}</h1>
                    <p v-if="store.address" class="text-sm text-gray-500">{{ store.address }}</p>
                    <p v-if="store.phone" class="text-sm text-gray-500">{{ store.phone }}</p>
                </div>

                <hr class="my-4 border-dashed" />

                <!-- Order Info -->
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-500">Order</span>
                    <span class="font-medium">{{ order.order_number }}</span>
                </div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-500">Date</span>
                    <span>{{ formatDate(order.created_at) }}</span>
                </div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-500">Type</span>
                    <span>{{ order.order_type === 'take_out' ? 'Take Out' : 'Dine In' }}</span>
                </div>
                <div v-if="order.customer" class="flex justify-between text-sm mb-1">
                    <span class="text-gray-500">Customer</span>
                    <span>{{ order.customer.name }}</span>
                </div>

                <hr class="my-4 border-dashed" />

                <!-- Items -->
                <div class="space-y-2">
                    <div v-for="(item, i) in order.items" :key="i" class="flex justify-between text-sm">
                        <div>
                            <span>{{ item.product_name }}</span>
                            <span class="text-gray-400 text-xs ml-1">x{{ item.quantity }}</span>
                        </div>
                        <span>{{ formatCurrency(item.subtotal) }}</span>
                    </div>
                </div>

                <hr class="my-4 border-dashed" />

                <!-- Totals -->
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Subtotal</span>
                        <span>{{ formatCurrency(order.subtotal) }}</span>
                    </div>
                    <div v-if="Number(order.discount_amount) > 0" class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>-{{ formatCurrency(order.discount_amount) }}</span>
                    </div>
                    <div v-if="Number(order.tax_amount) > 0" class="flex justify-between">
                        <span class="text-gray-500">Tax</span>
                        <span>{{ formatCurrency(order.tax_amount) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-dashed pt-2 text-base font-bold">
                        <span>Total</span>
                        <span>{{ formatCurrency(order.total) }}</span>
                    </div>
                </div>

                <!-- Payments -->
                <div v-if="order.payments.length > 0" class="mt-4">
                    <hr class="my-4 border-dashed" />
                    <div v-for="(p, i) in order.payments" :key="i" class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ paymentMethodLabel(p.method) }}</span>
                        <span>{{ formatCurrency(p.amount) }}</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 text-center text-sm text-gray-400">
                    Thank you for your purchase!
                </div>
            </div>

            <p class="mt-4 text-center text-xs text-gray-400">
                Powered by OmniPOS
            </p>
        </div>
    </div>
</template>
