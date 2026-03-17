<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import type { TenantSettings } from '@/types/tenant';
import { useTenant } from '@/composables/useTenant';

const props = defineProps<{
    settings: TenantSettings;
}>();

const { tenantUrl } = useTenant();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Settings', href: tenantUrl('settings') },
];

const form = useForm({
    store_name: props.settings.store_name ?? '',
    store_address: props.settings.store_address ?? '',
    store_phone: props.settings.store_phone ?? '',
    tax_rate: props.settings.tax_rate ?? 0,
    tax_label: props.settings.tax_label ?? '',
    tax_inclusive: props.settings.tax_inclusive ?? false,
    receipt_header: props.settings.receipt_header ?? '',
    receipt_footer: props.settings.receipt_footer ?? '',
    currency: props.settings.currency ?? 'PHP',
});

function submit() {
    form.put(tenantUrl('settings'));
}

// Live tax preview
const samplePrice = 100;
const taxRate = computed(() => Number(form.tax_rate) || 0);

const taxPreview = computed(() => {
    if (taxRate.value <= 0) return null;
    const afterDiscount = samplePrice;
    if (form.tax_inclusive) {
        const tax = Math.round((afterDiscount - afterDiscount / (1 + taxRate.value / 100)) * 100) / 100;
        return { price: samplePrice, tax, total: samplePrice, label: 'inclusive' };
    }
    const tax = Math.round(afterDiscount * (taxRate.value / 100) * 100) / 100;
    return { price: samplePrice, tax, total: samplePrice + tax, label: 'exclusive' };
});
</script>

<template>
    <Head title="Settings" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <h1 class="mb-6 text-2xl font-bold">Settings</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Store Information -->
                <div class="rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Store Information</h2>
                    <div class="space-y-4">
                        <div>
                            <Label for="store_name">Store Name</Label>
                            <Input
                                id="store_name"
                                v-model="form.store_name"
                                placeholder="My Store"
                                class="mt-1"
                            />
                            <p v-if="form.errors.store_name" class="mt-1 text-sm text-red-500">{{ form.errors.store_name }}</p>
                        </div>

                        <div>
                            <Label for="store_address">Address</Label>
                            <Textarea
                                id="store_address"
                                v-model="form.store_address"
                                placeholder="123 Main St, City"
                                rows="2"
                                class="mt-1"
                            />
                            <p v-if="form.errors.store_address" class="mt-1 text-sm text-red-500">{{ form.errors.store_address }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label for="store_phone">Phone</Label>
                                <Input
                                    id="store_phone"
                                    v-model="form.store_phone"
                                    placeholder="+63 912 345 6789"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.store_phone" class="mt-1 text-sm text-red-500">{{ form.errors.store_phone }}</p>
                            </div>

                            <div>
                                <Label for="currency">Currency</Label>
                                <Input
                                    id="currency"
                                    v-model="form.currency"
                                    placeholder="PHP"
                                    maxlength="10"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.currency" class="mt-1 text-sm text-red-500">{{ form.errors.currency }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tax Configuration -->
                <div class="rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Tax Configuration</h2>
                    <div class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label for="tax_rate">Tax Rate (%)</Label>
                                <Input
                                    id="tax_rate"
                                    v-model.number="form.tax_rate"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    placeholder="0"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.tax_rate" class="mt-1 text-sm text-red-500">{{ form.errors.tax_rate }}</p>
                            </div>

                            <div>
                                <Label for="tax_label">Tax Label</Label>
                                <Input
                                    id="tax_label"
                                    v-model="form.tax_label"
                                    placeholder="VAT"
                                    maxlength="50"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.tax_label" class="mt-1 text-sm text-red-500">{{ form.errors.tax_label }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <Checkbox
                                id="tax_inclusive"
                                :model-value="form.tax_inclusive"
                                @update:model-value="form.tax_inclusive = !!$event"
                                class="mt-0.5"
                            />
                            <div>
                                <Label for="tax_inclusive">Tax Inclusive</Label>
                                <p class="text-xs text-muted-foreground">
                                    When enabled, product prices already include tax. The tax amount will be extracted from the price instead of added on top.
                                </p>
                            </div>
                        </div>

                        <!-- Live Preview -->
                        <div v-if="taxPreview" class="rounded-lg bg-muted/50 p-4 text-sm">
                            <p class="mb-2 font-medium text-muted-foreground">Preview ({{ form.currency || 'PHP' }} {{ samplePrice.toFixed(2) }} item)</p>
                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span>Item price</span>
                                    <span>{{ samplePrice.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-muted-foreground">
                                    <span>{{ form.tax_label || 'Tax' }} ({{ taxRate }}%, {{ taxPreview.label }})</span>
                                    <span>{{ taxPreview.tax.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between font-bold border-t pt-1">
                                    <span>Total</span>
                                    <span>{{ taxPreview.total.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Receipt Customization -->
                <div class="rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Receipt Customization</h2>
                    <div class="space-y-4">
                        <div>
                            <Label for="receipt_header">Receipt Header</Label>
                            <Textarea
                                id="receipt_header"
                                v-model="form.receipt_header"
                                placeholder="Additional text shown at the top of receipts"
                                rows="2"
                                class="mt-1"
                            />
                            <p v-if="form.errors.receipt_header" class="mt-1 text-sm text-red-500">{{ form.errors.receipt_header }}</p>
                        </div>

                        <div>
                            <Label for="receipt_footer">Receipt Footer</Label>
                            <Textarea
                                id="receipt_footer"
                                v-model="form.receipt_footer"
                                placeholder="Thank you for your purchase!"
                                rows="2"
                                class="mt-1"
                            />
                            <p v-if="form.errors.receipt_footer" class="mt-1 text-sm text-red-500">{{ form.errors.receipt_footer }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Settings' }}
                    </Button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
