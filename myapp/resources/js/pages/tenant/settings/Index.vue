<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Briefcase, Crown, CreditCard, Globe, Monitor, Moon, Percent, ReceiptText, Store, Sun, Palette, TriangleAlert } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import type { TenantSettings } from '@/types/tenant';
import { useTenant } from '@/composables/useTenant';
import { useAppearance } from '@/composables/useAppearance';
import { useLocale } from '@/composables/useLocale';

const { t } = useI18n();
const { updateAppearance } = useAppearance();
const { setLocale } = useLocale();

const props = defineProps<{
    settings: TenantSettings;
    businessType: string;
    businessTypes: { value: string; label: string; icon: string }[];
    isOwner: boolean;
}>();

const { tenant, tenantUrl } = useTenant();
const plan = computed(() => tenant.value?.subscription?.plan ?? null);
const limitText = (n: number | null | undefined, unit: string) => (n == null ? `Unlimited ${unit}` : `Up to ${n} ${unit}`);
const isEnterprise = computed(() => tenant.value?.subscription?.plan?.slug === 'enterprise');

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.dashboard'), href: tenantUrl('dashboard') },
    { title: t('nav.settings'), href: tenantUrl('settings') },
]);

const currencies = [
    { code: 'PHP', label: 'PHP - Philippine Peso' },
    { code: 'USD', label: 'USD - US Dollar' },
    { code: 'EUR', label: 'EUR - Euro' },
    { code: 'GBP', label: 'GBP - British Pound' },
    { code: 'JPY', label: 'JPY - Japanese Yen' },
    { code: 'KRW', label: 'KRW - Korean Won' },
    { code: 'CNY', label: 'CNY - Chinese Yuan' },
    { code: 'SGD', label: 'SGD - Singapore Dollar' },
    { code: 'AUD', label: 'AUD - Australian Dollar' },
    { code: 'CAD', label: 'CAD - Canadian Dollar' },
    { code: 'INR', label: 'INR - Indian Rupee' },
    { code: 'THB', label: 'THB - Thai Baht' },
    { code: 'MYR', label: 'MYR - Malaysian Ringgit' },
    { code: 'IDR', label: 'IDR - Indonesian Rupiah' },
    { code: 'VND', label: 'VND - Vietnamese Dong' },
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
    default_theme: props.settings.default_theme ?? 'system',
    default_language: props.settings.default_language ?? 'en',
});

function submit() {
    form.put(tenantUrl('settings'));
}

const typeForm = useForm({
    business_type: props.businessType,
    confirmation: '',
});
const confirmOpen = ref(false);
const newTypeLabel = computed(() => props.businessTypes.find((b) => b.value === typeForm.business_type)?.label ?? '');

function changeType() {
    typeForm.put(tenantUrl('settings/business-type'), {
        preserveScroll: true,
        onSuccess: () => {
            confirmOpen.value = false;
            typeForm.reset('confirmation');
        },
    });
}

function setTheme(value: 'light' | 'dark' | 'system') {
    form.default_theme = value;
    updateAppearance(value);
}

function setLanguage(value: string) {
    form.default_language = value;
    setLocale(value);
}

// Live tax preview
const samplePrice = 100;
const taxRate = computed(() => Number(form.tax_rate) || 0);

const taxPreview = computed(() => {
    if (taxRate.value <= 0) return null;
    const afterDiscount = samplePrice;
    if (form.tax_inclusive) {
        const tax = Math.round((afterDiscount - afterDiscount / (1 + taxRate.value / 100)) * 100) / 100;
        return { price: samplePrice, tax, total: samplePrice, label: t('settings.inclusive') };
    }
    const tax = Math.round(afterDiscount * (taxRate.value / 100) * 100) / 100;
    return { price: samplePrice, tax, total: samplePrice + tax, label: t('settings.exclusive') };
});
</script>

<template>
    <Head :title="$t('settings.title')" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl p-6">
            <h1 class="mb-6 text-2xl font-bold">{{ $t('settings.title') }}</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Store Information -->
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                            <Store class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold">{{ $t('settings.storeInfo') }}</h2>
                            <p class="text-xs text-muted-foreground">{{ $t('settings.storeInfoDesc', 'Basic information about your store') }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <Label for="store_name">{{ $t('settings.storeName') }}</Label>
                            <Input
                                id="store_name"
                                v-model="form.store_name"
                                placeholder="My Store"
                                class="mt-1"
                            />
                            <p v-if="form.errors.store_name" class="mt-1 text-sm text-red-500">{{ form.errors.store_name }}</p>
                        </div>

                        <div>
                            <Label for="store_address">{{ $t('settings.storeAddress') }}</Label>
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
                                <Label for="store_phone">{{ $t('settings.storePhone') }}</Label>
                                <Input
                                    id="store_phone"
                                    v-model="form.store_phone"
                                    placeholder="+63 912 345 6789"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.store_phone" class="mt-1 text-sm text-red-500">{{ form.errors.store_phone }}</p>
                            </div>

                            <div>
                                <Label for="currency">{{ $t('settings.currency') }}</Label>
                                <Select v-model="form.currency">
                                    <SelectTrigger class="mt-1">
                                        <SelectValue placeholder="Select currency" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="c in currencies" :key="c.code" :value="c.code">
                                            {{ c.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.currency" class="mt-1 text-sm text-red-500">{{ form.errors.currency }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tax Configuration -->
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                            <Percent class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold">{{ $t('settings.taxConfig') }}</h2>
                            <p class="text-xs text-muted-foreground">{{ $t('settings.taxConfigDesc', 'Configure how taxes are calculated') }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label for="tax_rate">{{ $t('settings.taxRate') }}</Label>
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
                                <Label for="tax_label">{{ $t('settings.taxLabel') }}</Label>
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
                                <Label for="tax_inclusive">{{ $t('settings.taxInclusive') }}</Label>
                                <p class="text-xs text-muted-foreground">
                                    {{ $t('settings.taxInclusiveDesc') }}
                                </p>
                            </div>
                        </div>

                        <!-- Live Preview -->
                        <div v-if="taxPreview" class="rounded-lg bg-muted/50 p-4 text-sm">
                            <p class="mb-2 font-medium text-muted-foreground">{{ $t('settings.preview') }} ({{ form.currency || 'PHP' }} {{ samplePrice.toFixed(2) }} item)</p>
                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span>{{ $t('settings.itemPrice') }}</span>
                                    <span>{{ samplePrice.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-muted-foreground">
                                    <span>{{ form.tax_label || $t('common.tax') }} ({{ taxRate }}%, {{ taxPreview.label }})</span>
                                    <span>{{ taxPreview.tax.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between font-bold border-t pt-1">
                                    <span>{{ $t('common.total') }}</span>
                                    <span>{{ taxPreview.total.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Receipt Customization -->
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                            <ReceiptText class="h-5 w-5 text-primary" />
                        </div>
                        <div class="flex-1">
                            <h2 class="text-base font-semibold">{{ $t('settings.receiptCustom') }}</h2>
                            <p class="text-xs text-muted-foreground">{{ $t('settings.receiptCustomDesc', 'Header and footer text for printed receipts') }}</p>
                        </div>
                        <Link
                            :href="tenantUrl('receipt-customization')"
                            class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors hover:bg-muted"
                        >
                            <ReceiptText class="h-4 w-4" />
                            Customize Receipt
                            <Crown v-if="!isEnterprise" class="h-3.5 w-3.5 text-amber-500" />
                        </Link>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <Label for="receipt_header">{{ $t('settings.receiptHeader') }}</Label>
                            <Textarea
                                id="receipt_header"
                                v-model="form.receipt_header"
                                :placeholder="$t('settings.receiptHeaderPlaceholder')"
                                rows="2"
                                class="mt-1"
                            />
                            <p v-if="form.errors.receipt_header" class="mt-1 text-sm text-red-500">{{ form.errors.receipt_header }}</p>
                        </div>

                        <div>
                            <Label for="receipt_footer">{{ $t('settings.receiptFooter') }}</Label>
                            <Textarea
                                id="receipt_footer"
                                v-model="form.receipt_footer"
                                :placeholder="$t('settings.receiptFooterPlaceholder')"
                                rows="2"
                                class="mt-1"
                            />
                            <p v-if="form.errors.receipt_footer" class="mt-1 text-sm text-red-500">{{ form.errors.receipt_footer }}</p>
                        </div>
                    </div>
                </div>

                <!-- Appearance & Language -->
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                            <Palette class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold">{{ $t('settings.appearanceLang') }}</h2>
                            <p class="text-xs text-muted-foreground">{{ $t('settings.appearanceLangDesc', 'Theme and language preferences') }}</p>
                        </div>
                    </div>
                    <div class="space-y-5">
                        <div>
                            <Label>{{ $t('settings.defaultTheme') }}</Label>
                            <p class="text-xs text-muted-foreground mb-3">{{ $t('settings.defaultThemeDesc') }}</p>
                            <div class="grid grid-cols-3 gap-3">
                                <button
                                    type="button"
                                    @click="setTheme('light')"
                                    :class="[
                                        'flex flex-col items-center gap-1.5 rounded-lg border-2 p-3 text-sm transition-all',
                                        form.default_theme === 'light'
                                            ? 'border-primary bg-primary/5 text-primary shadow-sm'
                                            : 'border-transparent bg-muted/50 hover:bg-muted',
                                    ]"
                                >
                                    <Sun class="h-5 w-5" />
                                    <span class="font-medium">{{ $t('settings.light') }}</span>
                                </button>
                                <button
                                    type="button"
                                    @click="setTheme('dark')"
                                    :class="[
                                        'flex flex-col items-center gap-1.5 rounded-lg border-2 p-3 text-sm transition-all',
                                        form.default_theme === 'dark'
                                            ? 'border-primary bg-primary/5 text-primary shadow-sm'
                                            : 'border-transparent bg-muted/50 hover:bg-muted',
                                    ]"
                                >
                                    <Moon class="h-5 w-5" />
                                    <span class="font-medium">{{ $t('settings.dark') }}</span>
                                </button>
                                <button
                                    type="button"
                                    @click="setTheme('system')"
                                    :class="[
                                        'flex flex-col items-center gap-1.5 rounded-lg border-2 p-3 text-sm transition-all',
                                        form.default_theme === 'system'
                                            ? 'border-primary bg-primary/5 text-primary shadow-sm'
                                            : 'border-transparent bg-muted/50 hover:bg-muted',
                                    ]"
                                >
                                    <Monitor class="h-5 w-5" />
                                    <span class="font-medium">{{ $t('settings.system') }}</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <Label for="default_language">{{ $t('settings.defaultLanguage') }}</Label>
                            <p class="text-xs text-muted-foreground mb-2">{{ $t('settings.defaultLanguageDesc') }}</p>
                            <Select
                                :model-value="form.default_language"
                                @update:model-value="setLanguage"
                            >
                                <SelectTrigger class="w-[200px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="en">English</SelectItem>
                                    <SelectItem value="ja">Japanese (日本語)</SelectItem>
                                    <SelectItem value="fil">Filipino</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? $t('common.saving') : $t('settings.saveSettings') }}
                    </Button>
                </div>
            </form>

            <!-- Plan & Billing -->
            <div class="mt-6 rounded-xl border bg-card p-6 shadow-sm">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                        <CreditCard class="h-5 w-5 text-primary" />
                    </div>
                    <div class="flex-1">
                        <h2 class="text-base font-semibold">Plan &amp; Billing</h2>
                        <p class="text-xs text-muted-foreground">What your plan includes</p>
                    </div>
                    <span v-if="plan" class="rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-900/30 dark:text-teal-400">{{ plan.name }}</span>
                </div>
                <ul v-if="plan" class="grid gap-2 text-sm sm:grid-cols-3">
                    <li class="rounded-lg bg-muted/50 px-3 py-2">{{ limitText(plan.max_branches, 'branches') }}</li>
                    <li class="rounded-lg bg-muted/50 px-3 py-2">{{ limitText(plan.max_users, 'users') }}</li>
                    <li class="rounded-lg bg-muted/50 px-3 py-2">{{ limitText(plan.max_products, 'products') }}</li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">No plan is attached to this business yet.</p>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-muted-foreground">Online payment is coming soon. To change plans, send us a request and we will switch it for you.</p>
                    <Button as-child variant="outline" size="sm">
                        <a href="mailto:support@omnipos.shop?subject=Change%20my%20OmniPOS%20plan">Request a plan change</a>
                    </Button>
                </div>
            </div>

            <!-- Business Type (owner only) -->
            <div v-if="isOwner" class="mt-6 rounded-xl border border-red-200 bg-card p-6 shadow-sm dark:border-red-900/50">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-500/10">
                        <Briefcase class="h-5 w-5 text-red-600" />
                    </div>
                    <div>
                        <h2 class="text-base font-semibold">Business Type</h2>
                        <p class="text-xs text-muted-foreground">Changing this resets your products and categories.</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="w-full sm:w-72">
                        <Label>Business type</Label>
                        <Select v-model="typeForm.business_type">
                            <SelectTrigger class="mt-1">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="bt in businessTypes" :key="bt.value" :value="bt.value">
                                    {{ bt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="typeForm.business_type === businessType"
                        @click="confirmOpen = true"
                    >
                        Change business type
                    </Button>
                </div>
            </div>
        </div>

        <Dialog v-model:open="confirmOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-red-600">
                        <TriangleAlert class="h-5 w-5" />
                        Change business type?
                    </DialogTitle>
                    <DialogDescription>
                        Switching to <strong>{{ newTypeLabel }}</strong> affects all of your data and cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <ul class="list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                    <li>All <strong>products</strong>, variations, and stock levels will be deleted.</li>
                    <li>All <strong>categories</strong> will be deleted and replaced with the defaults for {{ newTypeLabel }}.</li>
                    <li>Purchase order and stock transfer lines for those products will be removed.</li>
                    <li>Past orders are kept, but will no longer link to a product.</li>
                </ul>
                <div>
                    <Label for="confirmation">Type <strong>CHANGE</strong> to confirm</Label>
                    <Input id="confirmation" v-model="typeForm.confirmation" class="mt-1" autocomplete="off" />
                    <p v-if="typeForm.errors.confirmation" class="mt-1 text-sm text-red-500">{{ typeForm.errors.confirmation }}</p>
                    <p v-if="typeForm.errors.business_type" class="mt-1 text-sm text-red-500">{{ typeForm.errors.business_type }}</p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="confirmOpen = false">Cancel</Button>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="typeForm.confirmation !== 'CHANGE' || typeForm.processing"
                        @click="changeType"
                    >
                        {{ typeForm.processing ? 'Changing...' : 'Yes, delete and change' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
