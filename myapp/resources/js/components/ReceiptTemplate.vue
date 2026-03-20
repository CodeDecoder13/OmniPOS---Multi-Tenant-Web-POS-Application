<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

export interface ReceiptData {
    storeName: string;
    storeAddress?: string;
    storePhone?: string;
    receiptHeader?: string;
    receiptFooter?: string;
    orderNumber?: string;
    dateTime: string;
    cashier: string;
    customer?: string | null;
    tableName?: string | null;
    items: {
        name: string;
        quantity: number;
        price: number;
        subtotal: number;
    }[];
    subtotal: number;
    discount?: number;
    promotionDiscount?: number;
    promotionCode?: string | null;
    tax?: number;
    taxLabel?: string;
    total: number;
    paymentMethod?: string;
    amountTendered?: number;
    change?: number;
    referenceNumber?: string | null;
    orderType?: string;
}

const props = defineProps<{
    data: ReceiptData;
    showPaymentDetails: boolean;
}>();

function formatCurrency(amount: number | string) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount));
}

function formatPaymentMethod(method: string) {
    if (method === 'e_wallet') return 'E-Wallet';
    return method.charAt(0).toUpperCase() + method.slice(1);
}
</script>

<template>
    <div class="mx-auto max-w-[302px] font-mono text-xs leading-relaxed text-black">
        <!-- Store Header -->
        <div class="text-center mb-2">
            <p class="text-sm font-bold">{{ data.storeName }}</p>
            <p v-if="data.storeAddress" class="text-[10px] text-gray-600">{{ data.storeAddress }}</p>
            <p v-if="data.storePhone" class="text-[10px] text-gray-600">{{ data.storePhone }}</p>
            <p v-if="data.receiptHeader" class="mt-1 text-[10px] text-gray-600 whitespace-pre-line">{{ data.receiptHeader }}</p>
        </div>

        <div class="border-t border-dashed border-gray-400 my-2" />

        <!-- Order Info -->
        <div class="space-y-0.5">
            <p v-if="data.orderNumber">{{ $t('receipt.order') }}: {{ data.orderNumber }}</p>
            <p>{{ $t('receipt.date') }}: {{ data.dateTime }}</p>
            <p>{{ $t('receipt.cashier') }}: {{ data.cashier }}</p>
            <p v-if="data.customer">{{ $t('receipt.customer') }}: {{ data.customer }}</p>
            <p v-if="data.tableName">{{ $t('receipt.table') }}: {{ data.tableName }}</p>
            <p v-if="data.orderType" class="font-bold">** {{ data.orderType }} **</p>
        </div>

        <div class="border-t border-dashed border-gray-400 my-2" />

        <!-- Items -->
        <div class="space-y-1">
            <div v-for="(item, i) in data.items" :key="i" class="flex justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="truncate">{{ item.name }}</p>
                    <p class="text-[10px] text-gray-600">
                        {{ item.quantity }} x {{ formatCurrency(item.price) }}
                    </p>
                </div>
                <span class="shrink-0">{{ formatCurrency(item.subtotal) }}</span>
            </div>
        </div>

        <div class="border-t border-dashed border-gray-400 my-2" />

        <!-- Totals -->
        <div class="space-y-0.5">
            <div class="flex justify-between">
                <span>{{ $t('receipt.subtotal') }}</span>
                <span>{{ formatCurrency(data.subtotal) }}</span>
            </div>
            <div v-if="data.discount && data.discount > 0" class="flex justify-between">
                <span>{{ $t('receipt.discount') }}</span>
                <span>-{{ formatCurrency(data.discount) }}</span>
            </div>
            <div v-if="data.promotionDiscount && data.promotionDiscount > 0" class="flex justify-between">
                <span>{{ $t('receipt.promo') }}{{ data.promotionCode ? ` (${data.promotionCode})` : '' }}</span>
                <span>-{{ formatCurrency(data.promotionDiscount) }}</span>
            </div>
            <div v-if="data.tax && data.tax > 0" class="flex justify-between">
                <span>{{ data.taxLabel || $t('common.tax') }}</span>
                <span>{{ formatCurrency(data.tax) }}</span>
            </div>
            <div class="flex justify-between font-bold text-sm border-t border-dashed border-gray-400 pt-1 mt-1">
                <span>{{ $t('receipt.total') }}</span>
                <span>{{ formatCurrency(data.total) }}</span>
            </div>
        </div>

        <!-- Payment Details -->
        <template v-if="showPaymentDetails && data.paymentMethod">
            <div class="border-t border-dashed border-gray-400 my-2" />
            <div class="space-y-0.5">
                <div class="flex justify-between">
                    <span>{{ $t('receipt.payment') }}</span>
                    <span>{{ formatPaymentMethod(data.paymentMethod) }}</span>
                </div>
                <div v-if="data.amountTendered" class="flex justify-between">
                    <span>{{ $t('receipt.tendered') }}</span>
                    <span>{{ formatCurrency(data.amountTendered) }}</span>
                </div>
                <div v-if="data.change && data.change > 0" class="flex justify-between">
                    <span>{{ $t('receipt.change') }}</span>
                    <span>{{ formatCurrency(data.change) }}</span>
                </div>
                <div v-if="data.referenceNumber" class="flex justify-between">
                    <span>{{ $t('receipt.ref') }}</span>
                    <span>{{ data.referenceNumber }}</span>
                </div>
            </div>
        </template>

        <div class="border-t border-dashed border-gray-400 my-2" />

        <!-- Footer -->
        <div class="text-center text-[10px] text-gray-500">
            <p v-if="data.receiptFooter" class="whitespace-pre-line">{{ data.receiptFooter }}</p>
            <p v-else>{{ $t('receipt.thankYou') }}</p>
        </div>
    </div>
</template>
