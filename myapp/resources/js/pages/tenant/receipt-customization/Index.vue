<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    Crown,
    ImagePlus,
    Trash2,
    Eye,
    EyeOff,
    Type,
    Ruler,
    Upload,
    Check,
    Printer,
    Receipt,
} from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import ReceiptTemplate from '@/components/ReceiptTemplate.vue';
import UpgradePlanModal from '@/components/UpgradePlanModal.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import type { BreadcrumbItem } from '@/types';
import type { TenantSettings } from '@/types/tenant';
import { useTenant } from '@/composables/useTenant';

const props = defineProps<{
    settings: TenantSettings;
}>();

const page = usePage();
const { tenant, tenantUrl } = useTenant();

const isEnterprise = computed(() => tenant.value?.subscription?.plan?.slug === 'enterprise');
const showUpgradeModal = ref(false);
const plans = computed(() => (page.props.plans ?? []) as any[]);
const currentPlanSlug = computed(() => tenant.value?.subscription?.plan?.slug ?? 'free');

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Settings', href: tenantUrl('settings') },
    { title: 'Receipt Design', href: tenantUrl('receipt-customization') },
]);

const form = useForm({
    receipt_header: props.settings.receipt_header ?? '',
    receipt_footer: props.settings.receipt_footer ?? '',
    receipt_thank_you_message: props.settings.receipt_thank_you_message ?? '',
    receipt_show_address: props.settings.receipt_show_address !== false,
    receipt_show_phone: props.settings.receipt_show_phone !== false,
    receipt_show_customer: props.settings.receipt_show_customer !== false,
    receipt_show_table: props.settings.receipt_show_table !== false,
    receipt_show_order_type: props.settings.receipt_show_order_type !== false,
    receipt_show_tax_breakdown: props.settings.receipt_show_tax_breakdown !== false,
    receipt_width: props.settings.receipt_width ?? '80mm',
    receipt_logo: null as File | null,
    remove_logo: false,
});

// Logo preview
const existingLogoUrl = ref<string | null>(props.settings.receipt_logo_url ?? null);
const logoPreviewUrl = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const displayLogoUrl = computed(() => {
    if (form.remove_logo) return null;
    return logoPreviewUrl.value ?? existingLogoUrl.value;
});

// Drag and drop
const isDragging = ref(false);

function onLogoSelected(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    handleLogoFile(file);
}

function handleLogoFile(file: File) {
    if (!file.type.startsWith('image/')) return;
    form.receipt_logo = file;
    form.remove_logo = false;
    logoPreviewUrl.value = URL.createObjectURL(file);
}

function onDrop(e: DragEvent) {
    isDragging.value = false;
    const file = e.dataTransfer?.files[0];
    if (file) handleLogoFile(file);
}

function removeLogo() {
    form.receipt_logo = null;
    form.remove_logo = true;
    logoPreviewUrl.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
}

function submit() {
    form.post(tenantUrl('receipt-customization'), {
        forceFormData: true,
    });
}

// Toggle descriptions
const toggleSections = [
    { key: 'receipt_show_address' as const, label: 'Store Address', description: '"123 Main Street, City" below store name' },
    { key: 'receipt_show_phone' as const, label: 'Store Phone', description: 'Phone number below address' },
    { key: 'receipt_show_customer' as const, label: 'Customer Name', description: 'Customer name in order info' },
    { key: 'receipt_show_table' as const, label: 'Table Name', description: 'Table number for dine-in orders' },
    { key: 'receipt_show_order_type' as const, label: 'Order Type', description: '"DINE IN" or "TAKE OUT" badge' },
    { key: 'receipt_show_tax_breakdown' as const, label: 'Tax Breakdown', description: 'Individual tax line before total' },
];

// Width options
const widthOptions = [
    { value: '58mm' as const, label: '58mm', subtitle: 'Narrow', description: 'Small portable printers' },
    { value: '80mm' as const, label: '80mm', subtitle: 'Standard', description: 'Standard thermal printers' },
];

// Realistic sample receipt data
const sampleData = computed(() => ({
    storeName: props.settings.store_name || 'My Store',
    storeAddress: props.settings.store_address || '123 Main Street, City',
    storePhone: props.settings.store_phone || '+63 912 345 6789',
    receiptHeader: form.receipt_header || undefined,
    receiptFooter: form.receipt_footer || undefined,
    orderNumber: 'ORD-2847',
    dateTime: new Date().toLocaleString(),
    cashier: 'Maria Santos',
    customer: 'Juan Dela Cruz',
    tableName: 'Table 5',
    orderType: 'Dine In',
    items: [
        { name: 'Caramel Macchiato (L)', quantity: 2, price: 185, subtotal: 370 },
        { name: 'Classic Beef Burger', quantity: 1, price: 295, subtotal: 295 },
        { name: 'Caesar Salad', quantity: 1, price: 220, subtotal: 220 },
        { name: 'Chocolate Lava Cake', quantity: 2, price: 165, subtotal: 330 },
        { name: 'Sparkling Water', quantity: 3, price: 75, subtotal: 225 },
    ],
    subtotal: 1440,
    discount: 0,
    promotionDiscount: 144,
    promotionCode: 'SAVE10',
    tax: 155.52,
    taxLabel: props.settings.tax_label || 'VAT',
    total: 1451.52,
    paymentMethod: 'cash',
    amountTendered: 1500,
    change: 48.48,
}));
</script>

<template>
    <Head title="Receipt Design" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <!-- Mobile layout -->
        <div class="p-4 pb-20 lg:hidden">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold">Receipt Design</h1>
                    <p class="text-xs text-muted-foreground">Customize how your receipts look.</p>
                </div>
                <div v-if="!isEnterprise" class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400">
                    <Crown class="h-3.5 w-3.5" />
                    Enterprise
                </div>
            </div>

            <div class="relative">
                <div
                    v-if="!isEnterprise"
                    class="absolute inset-0 z-10 flex flex-col items-center justify-center rounded-xl bg-background/80 backdrop-blur-sm"
                >
                    <Crown class="mb-3 h-10 w-10 text-amber-500" />
                    <h3 class="mb-1 text-lg font-semibold">Enterprise Feature</h3>
                    <p class="mb-4 max-w-sm text-center text-sm text-muted-foreground">
                        Upgrade to the Enterprise plan to unlock receipt customization.
                    </p>
                    <Button class="bg-amber-500 text-white hover:bg-amber-600" @click="showUpgradeModal = true">
                        <Crown class="mr-2 h-4 w-4" /> Upgrade Plan
                    </Button>
                </div>

                <Tabs default-value="customize">
                    <TabsList class="grid w-full grid-cols-2 mb-4">
                        <TabsTrigger value="customize">Customize</TabsTrigger>
                        <TabsTrigger value="preview">Preview</TabsTrigger>
                    </TabsList>

                    <TabsContent value="customize">
                        <div class="space-y-4">
                            <!-- Logo Upload -->
                            <div class="rounded-xl border bg-card p-4 shadow-sm">
                                <div class="mb-3 flex items-center gap-2">
                                    <ImagePlus class="h-4 w-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Store Logo</h2>
                                </div>
                                <input ref="fileInputRef" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="onLogoSelected" />
                                <div
                                    v-if="!displayLogoUrl"
                                    class="relative flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-6 transition-colors"
                                    :class="isDragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/25 hover:border-muted-foreground/50'"
                                    @click="fileInputRef?.click()"
                                    @dragover.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="onDrop"
                                >
                                    <Upload class="mb-1.5 h-6 w-6 text-muted-foreground/50" />
                                    <p class="text-xs font-medium text-muted-foreground">Drag logo here or click to browse</p>
                                    <p class="mt-0.5 text-[10px] text-muted-foreground/60">JPG, PNG, GIF, or WebP. Max 1MB.</p>
                                </div>
                                <div v-else class="group relative flex items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/25 bg-muted/30 px-4 py-4">
                                    <img :src="displayLogoUrl" alt="Logo preview" class="max-h-14 max-w-[60%] object-contain" />
                                    <div class="absolute inset-0 flex items-center justify-center gap-2 rounded-lg bg-background/80 opacity-0 transition-opacity group-hover:opacity-100">
                                        <Button size="sm" variant="outline" @click="fileInputRef?.click()">Change</Button>
                                        <Button size="sm" variant="destructive" @click="removeLogo"><Trash2 class="mr-1 h-3 w-3" /> Remove</Button>
                                    </div>
                                </div>
                                <p v-if="form.errors.receipt_logo" class="mt-1 text-xs text-red-500">{{ form.errors.receipt_logo }}</p>
                            </div>

                            <!-- Toggles -->
                            <div class="rounded-xl border bg-card p-4 shadow-sm">
                                <div class="mb-3 flex items-center gap-2">
                                    <Eye class="h-4 w-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Show / Hide Sections</h2>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="toggle in toggleSections" :key="toggle.key" class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <Label :for="toggle.key" class="text-sm">{{ toggle.label }}</Label>
                                            <p class="text-[10px] text-muted-foreground">{{ toggle.description }}</p>
                                        </div>
                                        <Switch :id="toggle.key" :model-value="(form[toggle.key] as boolean)" class="shrink-0" @update:model-value="(form[toggle.key] as boolean) = !!$event" />
                                    </div>
                                </div>
                            </div>

                            <!-- Custom Text -->
                            <div class="rounded-xl border bg-card p-4 shadow-sm">
                                <div class="mb-3 flex items-center gap-2">
                                    <Type class="h-4 w-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Custom Text</h2>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <Label for="receipt_header_m">Header</Label>
                                        <Textarea id="receipt_header_m" v-model="form.receipt_header" placeholder="e.g., TIN: 123-456-789" rows="2" class="mt-1" />
                                    </div>
                                    <div>
                                        <Label for="receipt_footer_m">Footer</Label>
                                        <Textarea id="receipt_footer_m" v-model="form.receipt_footer" placeholder="e.g., Returns accepted within 7 days" rows="2" class="mt-1" />
                                    </div>
                                    <div>
                                        <Label for="thank_you_m">Thank You Message</Label>
                                        <Input id="thank_you_m" v-model="form.receipt_thank_you_message" placeholder="Thank you for your purchase!" class="mt-1" />
                                    </div>
                                </div>
                            </div>

                            <!-- Width -->
                            <div class="rounded-xl border bg-card p-4 shadow-sm">
                                <div class="mb-3 flex items-center gap-2">
                                    <Ruler class="h-4 w-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Receipt Width</h2>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        v-for="opt in widthOptions" :key="opt.value" type="button"
                                        class="relative flex flex-col items-center rounded-lg border-2 p-3 transition-all"
                                        :class="form.receipt_width === opt.value ? 'border-primary bg-primary/5' : 'border-muted hover:border-muted-foreground/30'"
                                        @click="form.receipt_width = opt.value"
                                    >
                                        <div v-if="form.receipt_width === opt.value" class="absolute right-1.5 top-1.5 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-primary">
                                            <Check class="h-2 w-2 text-primary-foreground" />
                                        </div>
                                        <span class="text-sm font-semibold">{{ opt.label }}</span>
                                        <span class="text-[10px] text-muted-foreground">{{ opt.subtitle }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </TabsContent>

                    <TabsContent value="preview">
                        <div class="flex flex-col items-center py-4">
                            <div :class="form.receipt_width === '58mm' ? 'w-[260px]' : 'w-[340px]'" class="transition-all duration-300">
                                <div class="h-3 w-full rounded-t-sm bg-gradient-to-b from-gray-300 to-[#FFFEF5] dark:from-gray-600 dark:to-[#2a2a25]" />
                                <div class="w-full bg-[#FFFEF5] px-4 py-6 shadow-[0_2px_15px_rgba(0,0,0,0.1)] dark:bg-[#2a2a25]">
                                    <ReceiptTemplate :data="sampleData" :show-payment-details="true" :logo-url="displayLogoUrl" :show-address="form.receipt_show_address" :show-phone="form.receipt_show_phone" :show-customer="form.receipt_show_customer" :show-table="form.receipt_show_table" :show-order-type="form.receipt_show_order_type" :show-tax-breakdown="form.receipt_show_tax_breakdown" :thank-you-message="form.receipt_thank_you_message" :width="form.receipt_width as '58mm' | '80mm'" />
                                </div>
                                <div class="h-3 w-full" style="background: linear-gradient(135deg, #FFFEF5 33.33%, transparent 33.33%) 0 0, linear-gradient(225deg, #FFFEF5 33.33%, transparent 33.33%) 0 0; background-size: 10px 100%; background-repeat: repeat-x;" />
                            </div>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>

            <!-- Mobile unsaved changes bar -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="translate-y-full opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-full opacity-0"
            >
                <div v-if="form.isDirty" class="fixed inset-x-0 bottom-0 z-30 border-t bg-background/95 px-4 py-3 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] backdrop-blur-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm text-muted-foreground">Unsaved changes</p>
                        <Button size="sm" :disabled="form.processing || !isEnterprise" @click="submit">
                            {{ form.processing ? 'Saving...' : 'Save' }}
                        </Button>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Desktop: Full-height two-panel layout (preview LEFT, controls RIGHT) -->
        <div class="hidden lg:flex lg:h-[calc(100vh-4rem)]">
            <div class="relative flex w-full">
                <!-- Enterprise overlay -->
                <div
                    v-if="!isEnterprise"
                    class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-background/80 backdrop-blur-sm"
                >
                    <Crown class="mb-3 h-10 w-10 text-amber-500" />
                    <h3 class="mb-1 text-lg font-semibold">Enterprise Feature</h3>
                    <p class="mb-4 max-w-sm text-center text-sm text-muted-foreground">
                        Upgrade to the Enterprise plan to unlock receipt customization.
                    </p>
                    <Button class="bg-amber-500 text-white hover:bg-amber-600" @click="showUpgradeModal = true">
                        <Crown class="mr-2 h-4 w-4" /> Upgrade Plan
                    </Button>
                </div>

                <!-- LEFT: Live Preview (fixed, centered) -->
                <div class="flex flex-1 items-start justify-center overflow-y-auto border-r bg-muted/30 p-6 dark:bg-muted/10">
                    <div class="sticky top-0 flex flex-col items-center pt-4">
                        <div class="mb-3 flex items-center gap-2">
                            <Receipt class="h-3.5 w-3.5 text-muted-foreground" />
                            <span class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Live Preview</span>
                        </div>

                        <div :class="form.receipt_width === '58mm' ? 'w-[250px]' : 'w-[320px]'" class="transition-all duration-300">
                            <!-- Paper roll top edge -->
                            <div class="h-3 w-full rounded-t-sm bg-gradient-to-b from-gray-300 to-[#FFFEF5] dark:from-gray-600 dark:to-[#2a2a25]" />
                            <!-- Receipt body -->
                            <div class="w-full bg-[#FFFEF5] px-4 py-6 shadow-[0_2px_15px_rgba(0,0,0,0.1)] dark:bg-[#2a2a25]">
                                <div class="transition-all duration-300">
                                    <ReceiptTemplate
                                        :data="sampleData"
                                        :show-payment-details="true"
                                        :logo-url="displayLogoUrl"
                                        :show-address="form.receipt_show_address"
                                        :show-phone="form.receipt_show_phone"
                                        :show-customer="form.receipt_show_customer"
                                        :show-table="form.receipt_show_table"
                                        :show-order-type="form.receipt_show_order_type"
                                        :show-tax-breakdown="form.receipt_show_tax_breakdown"
                                        :thank-you-message="form.receipt_thank_you_message"
                                        :width="form.receipt_width as '58mm' | '80mm'"
                                    />
                                </div>
                            </div>
                            <!-- Torn edge bottom (zigzag) -->
                            <div class="h-3 w-full dark:hidden" style="background: linear-gradient(135deg, #FFFEF5 33.33%, transparent 33.33%) 0 0, linear-gradient(225deg, #FFFEF5 33.33%, transparent 33.33%) 0 0; background-size: 10px 100%; background-repeat: repeat-x;" />
                            <div class="hidden h-3 w-full dark:block" style="background: linear-gradient(135deg, #2a2a25 33.33%, transparent 33.33%) 0 0, linear-gradient(225deg, #2a2a25 33.33%, transparent 33.33%) 0 0; background-size: 10px 100%; background-repeat: repeat-x;" />
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Controls (scrollable) -->
                <div class="w-[420px] shrink-0 overflow-y-auto">
                    <div class="space-y-4 p-5">
                        <!-- Header -->
                        <div>
                            <h1 class="text-lg font-bold">Receipt Design</h1>
                            <p class="text-xs text-muted-foreground">Customize how your receipts look when printed or shared.</p>
                        </div>

                        <!-- Logo Upload -->
                        <div class="rounded-xl border bg-card p-4 shadow-sm">
                            <div class="mb-3 flex items-center gap-2">
                                <ImagePlus class="h-4 w-4 text-muted-foreground" />
                                <h2 class="text-sm font-semibold">Store Logo</h2>
                            </div>
                            <p class="mb-2 text-[10px] text-muted-foreground">Upload your store logo to appear at the top of receipts.</p>

                            <input ref="fileInputRef" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="onLogoSelected" />

                            <div
                                v-if="!displayLogoUrl"
                                class="relative flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-6 transition-colors"
                                :class="isDragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/25 hover:border-muted-foreground/50'"
                                @click="fileInputRef?.click()"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="onDrop"
                            >
                                <Upload class="mb-1.5 h-6 w-6 text-muted-foreground/50" />
                                <p class="text-xs font-medium text-muted-foreground">Drag logo here or click to browse</p>
                                <p class="mt-0.5 text-[10px] text-muted-foreground/60">JPG, PNG, GIF, or WebP. Max 1MB.</p>
                            </div>

                            <div v-else class="group relative flex items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/25 bg-muted/30 px-4 py-5">
                                <img :src="displayLogoUrl" alt="Logo preview" class="max-h-14 max-w-[60%] object-contain" />
                                <div class="absolute inset-0 flex items-center justify-center gap-2 rounded-lg bg-background/80 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Button size="sm" variant="outline" @click="fileInputRef?.click()">Change</Button>
                                    <Button size="sm" variant="destructive" @click="removeLogo"><Trash2 class="mr-1 h-3 w-3" /> Remove</Button>
                                </div>
                            </div>
                            <p v-if="form.errors.receipt_logo" class="mt-1 text-xs text-red-500">{{ form.errors.receipt_logo }}</p>
                        </div>

                        <!-- Section Toggles -->
                        <div class="rounded-xl border bg-card p-4 shadow-sm">
                            <div class="mb-3 flex items-center gap-2">
                                <Eye class="h-4 w-4 text-muted-foreground" />
                                <h2 class="text-sm font-semibold">Show / Hide Sections</h2>
                            </div>
                            <div class="space-y-3">
                                <div v-for="toggle in toggleSections" :key="toggle.key" class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <Label :for="toggle.key" class="text-sm">{{ toggle.label }}</Label>
                                        <p class="text-[10px] text-muted-foreground">{{ toggle.description }}</p>
                                    </div>
                                    <Switch :id="toggle.key" :model-value="(form[toggle.key] as boolean)" class="shrink-0" @update:model-value="(form[toggle.key] as boolean) = !!$event" />
                                </div>
                            </div>
                        </div>

                        <!-- Custom Text -->
                        <div class="rounded-xl border bg-card p-4 shadow-sm">
                            <div class="mb-3 flex items-center gap-2">
                                <Type class="h-4 w-4 text-muted-foreground" />
                                <h2 class="text-sm font-semibold">Custom Text</h2>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <Label for="receipt_header">Receipt Header</Label>
                                    <Textarea id="receipt_header" v-model="form.receipt_header" placeholder="e.g., TIN: 123-456-789&#10;Official Receipt" rows="2" class="mt-1" />
                                    <p v-if="form.errors.receipt_header" class="mt-0.5 text-xs text-red-500">{{ form.errors.receipt_header }}</p>
                                </div>
                                <div>
                                    <Label for="receipt_footer">Receipt Footer</Label>
                                    <Textarea id="receipt_footer" v-model="form.receipt_footer" placeholder="e.g., Returns accepted within 7 days" rows="2" class="mt-1" />
                                    <p v-if="form.errors.receipt_footer" class="mt-0.5 text-xs text-red-500">{{ form.errors.receipt_footer }}</p>
                                </div>
                                <div>
                                    <Label for="thank_you">Thank You Message</Label>
                                    <Input id="thank_you" v-model="form.receipt_thank_you_message" placeholder="Thank you for your purchase!" class="mt-1" />
                                    <p v-if="form.errors.receipt_thank_you_message" class="mt-0.5 text-xs text-red-500">{{ form.errors.receipt_thank_you_message }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Receipt Width -->
                        <div class="rounded-xl border bg-card p-4 shadow-sm">
                            <div class="mb-3 flex items-center gap-2">
                                <Ruler class="h-4 w-4 text-muted-foreground" />
                                <h2 class="text-sm font-semibold">Receipt Width</h2>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="opt in widthOptions" :key="opt.value" type="button"
                                    class="relative flex flex-col items-center rounded-lg border-2 p-3 transition-all"
                                    :class="form.receipt_width === opt.value ? 'border-primary bg-primary/5 shadow-sm' : 'border-muted hover:border-muted-foreground/30'"
                                    @click="form.receipt_width = opt.value"
                                >
                                    <div v-if="form.receipt_width === opt.value" class="absolute right-1.5 top-1.5 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-primary">
                                        <Check class="h-2 w-2 text-primary-foreground" />
                                    </div>
                                    <div class="mb-1.5 flex items-end justify-center">
                                        <div class="rounded-sm border border-current opacity-40" :class="opt.value === '58mm' ? 'h-7 w-4' : 'h-7 w-6'">
                                            <div class="mx-auto mt-1 space-y-0.5 px-0.5">
                                                <div class="h-px rounded bg-current opacity-60" />
                                                <div class="h-px rounded bg-current opacity-40" />
                                                <div class="h-px rounded bg-current opacity-60" />
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold">{{ opt.label }}</span>
                                    <span class="text-[10px] text-muted-foreground">{{ opt.subtitle }}</span>
                                    <span class="text-[9px] text-muted-foreground/70">{{ opt.description }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <Button
                            type="button"
                            class="w-full"
                            :disabled="form.processing || !isEnterprise"
                            @click="submit"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <UpgradePlanModal
            :open="showUpgradeModal"
            :current-plan-slug="currentPlanSlug"
            :plans="plans"
            resource="branches"
            @update:open="showUpgradeModal = $event"
        />
    </TenantLayout>
</template>
