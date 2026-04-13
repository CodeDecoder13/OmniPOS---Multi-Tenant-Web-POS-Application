<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { Minus, Plus, Search, ShoppingCart, Trash2, User, UserPlus, X, Receipt, Percent, DollarSign, Printer, Download, UtensilsCrossed, ShoppingBag, Pause, Play, Tag, ScanBarcode, History, LayoutGrid, List, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import PosLayout from '@/layouts/PosLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import PosLoginModal from '@/components/PosLoginModal.vue';
import StartShiftDialog from '@/components/StartShiftDialog.vue';
import ReceiptTemplate from '@/components/ReceiptTemplate.vue';
import DiscountPanel from '@/components/DiscountPanel.vue';
import type { ReceiptData } from '@/components/ReceiptTemplate.vue';
import { useTenant } from '@/composables/useTenant';
import { useCurrency } from '@/composables/useCurrency';
import { usePermissions } from '@/composables/usePermissions';
import { usePosAuth } from '@/composables/usePosAuth';
import { usePosShift } from '@/composables/usePosShift';
import { useBarcodeScanner } from '@/composables/useBarcodeScanner';
import { usePrinter } from '@/composables/usePrinter';
import type { KotData } from '@/composables/usePrinter';
import { useBranchSettings } from '@/composables/useBranchSettings';

import type { Category, Product, VariationGroup, Addon, Table } from '@/types';

interface CartItemVariation {
    variation_group_name: string;
    option_name: string;
    price_modifier: number;
}

interface VariationSelection {
    group_name: string;
    option_name: string;
    price_modifier: number;
}

interface CartItemAddon {
    addon_name: string;
    addon_price: number;
}

interface CartItem {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
    image_url?: string | null;
    variations?: CartItemVariation[];
    addons?: CartItemAddon[];
    notes?: string;
}

interface PosCustomer {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
}

const props = defineProps<{
    categories: Pick<Category, 'id' | 'name'>[];
    tables: Pick<Table, 'id' | 'name' | 'capacity' | 'status'>[];
    presetDiscounts?: { id: number; code: string; name: string; type: string; value: string }[];
    branchSettings?: Record<string, boolean> | null;
}>();

const { tenantUrl, tenant } = useTenant();
const { formatCurrency } = useCurrency();
const { can } = usePermissions();
const { isAuthenticated, operatorCan, operatorUserId, operatorName } = usePosAuth();
const { hasActiveShift, checkShiftStatus, refreshSummary, clearShift } = usePosShift();
const { printReceipt: doPrintReceipt, printKot } = usePrinter();
const { isEnabled } = useBranchSettings();


const showStartShift = ref(false);

async function checkAndPromptShift() {
    if (operatorUserId.value) {
        await checkShiftStatus(operatorUserId.value);
        if (!hasActiveShift.value) {
            showStartShift.value = true;
        }
    }
}

function onShiftStarted() {
    showStartShift.value = false;
}

// Watch for authentication changes — triggers after PIN login
// (cannot rely on @authenticated emit because PosLoginModal unmounts before event reaches parent)
watch(isAuthenticated, (authenticated) => {
    if (authenticated) {
        checkAndPromptShift();
    }
});

// When shift ends (hasActiveShift goes false), prompt operator to start a new shift.
// Also prevents focus from falling to the "Exit POS" link after ShiftSummaryDialog closes.
watch(hasActiveShift, (active, wasActive) => {
    if (wasActive && !active && isAuthenticated.value) {
        nextTick(() => {
            showStartShift.value = true;
        });
    }
});

// Product grid state
const showImages = ref(localStorage.getItem('pos-show-images') !== 'false');

watch(showImages, (val) => {
    localStorage.setItem('pos-show-images', String(val));
});

// Category scroll navigation
const categoryScrollRef = ref<HTMLElement | null>(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

function updateCategoryScroll() {
    const el = categoryScrollRef.value;
    if (!el) return;
    canScrollLeft.value = el.scrollLeft > 0;
    canScrollRight.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 1;
}

function scrollCategories(direction: 'left' | 'right') {
    const el = categoryScrollRef.value;
    if (!el) return;
    el.scrollBy({ left: direction === 'left' ? -200 : 200, behavior: 'smooth' });
}

watch(categoryScrollRef, (el) => {
    if (el) nextTick(() => updateCategoryScroll());
});

const products = ref<Product[]>([]);
const productSearch = ref('');
const selectedCategory = ref<number | null>(null);
const loadingProducts = ref(false);
const productPage = ref(1);
const hasMoreProducts = ref(false);

// Cart state
const cart = ref<CartItem[]>([]);
const availableOrderTypes = computed(() => {
    const types: ('dine_in' | 'take_out')[] = [];
    if (isEnabled('dine_in')) types.push('dine_in');
    if (isEnabled('takeout')) types.push('take_out');
    return types;
});
const orderType = ref<'dine_in' | 'take_out'>(isEnabled('dine_in') ? 'dine_in' : 'take_out');
const selectedTable = ref<number | null>(null);
const discountAmount = ref<number>(0);
const discountType = ref<'percentage' | 'fixed'>('percentage');
const orderNotes = ref('');

// Promo code state
const promoCode = ref('');
const appliedPromo = ref<{ id: number; code: string; name: string; type: string; value: string } | null>(null);
const promoDiscount = ref(0);
const promoLoading = ref(false);
const promoError = ref('');

async function applyPromoCode() {
    if (!promoCode.value.trim()) return;
    promoLoading.value = true;
    promoError.value = '';

    try {
        const xsrfToken = decodeURIComponent(
            document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
        );
        const res = await fetch(tenantUrl('pos/promotions/apply'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
            body: JSON.stringify({
                code: promoCode.value.trim(),
                subtotal: afterDiscount.value,
            }),
        });
        const data = await res.json();
        if (data.success) {
            appliedPromo.value = data.promotion;
            promoDiscount.value = data.discount;
            promoCode.value = '';
            showDiscountModal.value = false;
        } else {
            promoError.value = data.message;
        }
    } catch {
        promoError.value = 'Failed to apply promo code.';
    } finally {
        promoLoading.value = false;
    }
}

// Discount panel state
const showDiscountPanel = ref(false);
const showDiscountModal = ref(false);
const appliedPresetDiscount = ref<{ id: number; code: string; name: string; type: string; value: string } | null>(null);
const discountCustomer = ref<{ name: string; id_number: string; birthday: string } | null>(null);

function applyPresetDiscount(discount: { id: number; code: string; name: string; type: string; value: string }, customer: { name: string; id_number: string; birthday: string }) {
    // Apply as promotion (uses the promotion flow)
    appliedPromo.value = discount;
    promoDiscount.value = Math.round(afterDiscount.value * (parseFloat(discount.value) / 100) * 100) / 100;
    appliedPresetDiscount.value = discount;
    discountCustomer.value = customer;
    promoCode.value = '';
    promoError.value = '';
    showDiscountModal.value = false;
}

function removePresetDiscount() {
    appliedPresetDiscount.value = null;
    discountCustomer.value = null;
    removePromo();
}

function removePromo() {
    appliedPromo.value = null;
    promoDiscount.value = 0;
    promoError.value = '';
}

// Customer state
const selectedCustomer = ref<PosCustomer | null>(null);
const customerSearch = ref('');
const customerResults = ref<PosCustomer[]>([]);
const showCustomerSearch = ref(false);
const searchingCustomers = ref(false);

// Add customer modal
const showAddCustomer = ref(false);
const newCustomer = ref({ name: '', email: '', phone: '' });
const newCustomerErrors = ref<Record<string, string>>({});
const savingCustomer = ref(false);

function openAddCustomer() {
    newCustomer.value = { name: '', email: '', phone: '' };
    newCustomerErrors.value = {};
    showAddCustomer.value = true;
}

async function saveNewCustomer() {
    newCustomerErrors.value = {};
    if (!newCustomer.value.name.trim()) {
        newCustomerErrors.value.name = 'Name is required.';
        return;
    }
    savingCustomer.value = true;
    try {
        const xsrfToken = decodeURIComponent(
            document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
        );
        const res = await fetch(tenantUrl('pos/customers'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
            body: JSON.stringify({
                name: newCustomer.value.name.trim(),
                email: newCustomer.value.email.trim() || null,
                phone: newCustomer.value.phone.trim() || null,
            }),
        });
        if (!res.ok) {
            const err = await res.json();
            if (err.errors) {
                for (const [key, msgs] of Object.entries(err.errors)) {
                    newCustomerErrors.value[key] = (msgs as string[])[0];
                }
            } else {
                newCustomerErrors.value.name = err.message || 'Failed to create customer.';
            }
            return;
        }
        const created: PosCustomer = await res.json();
        selectCustomer(created);
        showAddCustomer.value = false;
    } catch {
        newCustomerErrors.value.name = 'Network error. Please try again.';
    } finally {
        savingCustomer.value = false;
    }
}

// Barcode scanner state
const barcodeInput = ref('');

async function handleBarcodeScan() {
    const sku = barcodeInput.value.trim();
    if (!sku) return;

    try {
        const params = new URLSearchParams({ search: sku });
        const res = await fetch(`${tenantUrl('pos/products')}?${params}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        const product = data.data?.find((p: Product) => p.sku === sku);
        if (product) {
            handleProductClick(product);
        }
    } catch { /* silently fail */ }
    barcodeInput.value = '';
}

// Barcode scanner composable (detects rapid keystrokes from hardware scanner)
async function handleBarcodeDetected(barcode: string): Promise<boolean> {
    try {
        const params = new URLSearchParams({ search: barcode });
        const res = await fetch(`${tenantUrl('pos/products')}?${params}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        const product = data.data?.find((p: Product) => p.sku === barcode);
        if (product) {
            handleProductClick(product);
            return true;
        }
    } catch { /* silently fail */ }
    return false;
}

const { scanStatus } = useBarcodeScanner(handleBarcodeDetected);

// Variation/addon modal state
const showVariationModal = ref(false);
const variationProduct = ref<(Product & { variation_groups?: VariationGroup[]; addons?: Addon[] }) | null>(null);
const selectedVariations = ref<Record<number, VariationSelection>>({});
const selectedAddons = ref<Record<number, boolean>>({});

function handleProductClick(product: Product & { variation_groups?: VariationGroup[]; addons?: Addon[] }) {
    const hasVariations = product.variation_groups && product.variation_groups.length > 0;
    const hasAddons = product.addons && product.addons.length > 0;

    if (hasVariations || hasAddons) {
        variationProduct.value = product;
        selectedVariations.value = {};
        selectedAddons.value = {};
        showVariationModal.value = true;
    } else {
        addToCart(product);
    }
}

function confirmVariationSelection() {
    if (!variationProduct.value) return;
    const product = variationProduct.value;

    const variations: CartItemVariation[] = Object.values(selectedVariations.value).map(v => ({
        variation_group_name: v.group_name,
        option_name: v.option_name,
        price_modifier: v.price_modifier,
    }));
    const addons: CartItemAddon[] = [];

    if (product.addons) {
        for (const addon of product.addons) {
            if (selectedAddons.value[addon.id]) {
                addons.push({ addon_name: addon.name, addon_price: Number(addon.price) });
            }
        }
    }

    const variationTotal = variations.reduce((sum, v) => sum + v.price_modifier, 0);
    const addonTotal = addons.reduce((sum, a) => sum + a.addon_price, 0);
    const totalPrice = Number(product.price) + variationTotal + addonTotal;

    // Build display name
    const extras = [...variations.map(v => v.option_name), ...addons.map(a => a.addon_name)];
    const displayName = extras.length > 0 ? `${product.name} (${extras.join(', ')})` : product.name;

    cart.value.push({
        product_id: product.id,
        name: displayName,
        price: totalPrice,
        quantity: 1,
        image_url: product.image_url,
        variations,
        addons,
    });

    showVariationModal.value = false;
    variationProduct.value = null;
}

// Payment dialog state
const showPaymentDialog = ref(false);
const paymentMethod = ref('cash');
const amountTendered = ref<number>(0);
const referenceNumber = ref('');
const processing = ref(false);

// Split payment state
interface PaymentEntry {
    method: string;
    amount: number;
    reference_number: string;
}
const splitPayments = ref<PaymentEntry[]>([]);
const splitMode = ref(false);

function addSplitPayment() {
    const remaining = total.value - splitPayments.value.reduce((s, p) => s + p.amount, 0);
    splitPayments.value.push({ method: 'cash', amount: Math.max(0, remaining), reference_number: '' });
}

function removeSplitPayment(index: number) {
    splitPayments.value.splice(index, 1);
    if (splitPayments.value.length === 0) splitMode.value = false;
}

const splitRemaining = computed(() => {
    return Math.max(0, total.value - splitPayments.value.reduce((s, p) => s + p.amount, 0));
});

function toggleSplitMode() {
    splitMode.value = !splitMode.value;
    if (splitMode.value && splitPayments.value.length === 0) {
        splitPayments.value = [{ method: 'cash', amount: total.value, reference_number: '' }];
    }
}

// Receipt preview state
const showReceiptPreview = ref(false);

// Receipt dialog state
const showReceiptDialog = ref(false);
const completedOrder = ref<any>(null);

// Computed values
const subtotal = computed(() => cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0));

const discountValue = computed(() => {
    if (!discountAmount.value) return 0;
    if (discountType.value === 'percentage') {
        return Math.round(subtotal.value * (discountAmount.value / 100) * 100) / 100;
    }
    return discountAmount.value;
});

const afterDiscount = computed(() => Math.max(0, subtotal.value - discountValue.value));
const afterPromo = computed(() => Math.max(0, afterDiscount.value - promoDiscount.value));

const hasActiveDiscount = computed(() =>
    discountAmount.value > 0 || appliedPromo.value !== null || appliedPresetDiscount.value !== null
);

const taxRate = computed(() => Number(tenant.value?.settings?.tax_rate) || 0);
const taxInclusive = computed(() => tenant.value?.settings?.tax_inclusive ?? false);
const taxLabel = computed(() => tenant.value?.settings?.tax_label || 'Tax');

const taxAmount = computed(() => {
    if (taxRate.value <= 0) return 0;
    if (taxInclusive.value) {
        return Math.round((afterPromo.value - afterPromo.value / (1 + taxRate.value / 100)) * 100) / 100;
    }
    return Math.round(afterPromo.value * (taxRate.value / 100) * 100) / 100;
});

const total = computed(() => {
    if (taxInclusive.value || taxRate.value <= 0) return afterPromo.value;
    return afterPromo.value + taxAmount.value;
});
const changeAmount = computed(() => paymentMethod.value === 'cash' ? Math.max(0, amountTendered.value - total.value) : 0);
const canCheckout = computed(() => {
    if (cart.value.length === 0) return false;
    if (splitMode.value) {
        return splitRemaining.value <= 0 && splitPayments.value.length > 0;
    }
    if (paymentMethod.value === 'cash' && amountTendered.value < total.value) return false;
    return true;
});
const cartItemCount = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0));

// Fetch products
async function fetchProducts(reset = false) {
    if (reset) productPage.value = 1;
    loadingProducts.value = true;

    const params = new URLSearchParams();
    if (productSearch.value) params.set('search', productSearch.value);
    if (selectedCategory.value) params.set('category_id', String(selectedCategory.value));
    params.set('page', String(productPage.value));

    try {
        const res = await fetch(`${tenantUrl('pos/products')}?${params}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();

        if (reset) {
            products.value = data.data;
        } else {
            products.value.push(...data.data);
        }
        hasMoreProducts.value = data.current_page < data.last_page;
    } catch {
        // silently fail
    } finally {
        loadingProducts.value = false;
    }
}

// Search customers
let customerDebounce: ReturnType<typeof setTimeout>;
async function searchCustomers() {
    clearTimeout(customerDebounce);
    if (!customerSearch.value || customerSearch.value.length < 2) {
        customerResults.value = [];
        return;
    }
    customerDebounce = setTimeout(async () => {
        searchingCustomers.value = true;
        try {
            const params = new URLSearchParams({ search: customerSearch.value });
            const res = await fetch(`${tenantUrl('pos/customers/search')}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            customerResults.value = await res.json();
        } catch {
            customerResults.value = [];
        } finally {
            searchingCustomers.value = false;
        }
    }, 300);
}

watch(customerSearch, searchCustomers);

// Cart operations
function addToCart(product: Product) {
    const existing = cart.value.find(item => item.product_id === product.id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.value.push({
            product_id: product.id,
            name: product.name,
            price: Number(product.price),
            quantity: 1,
            image_url: product.image_url,
        });
    }
}

function updateQuantity(index: number, delta: number) {
    const item = cart.value[index];
    item.quantity += delta;
    if (item.quantity <= 0) {
        cart.value.splice(index, 1);
    }
}

function removeFromCart(index: number) {
    cart.value.splice(index, 1);
}

function clearCart() {
    cart.value = [];
    orderType.value = availableOrderTypes.value[0] ?? 'dine_in';
    selectedTable.value = null;
    discountAmount.value = 0;
    orderNotes.value = '';
    selectedCustomer.value = null;
    appliedPromo.value = null;
    promoCode.value = '';
    promoDiscount.value = 0;
    appliedPresetDiscount.value = null;
    discountCustomer.value = null;
    recalledOrderId.value = null;
}

// Category filter
function selectCategory(categoryId: number | null) {
    selectedCategory.value = selectedCategory.value === categoryId ? null : categoryId;
    fetchProducts(true);
}

// Product search debounce
let productDebounce: ReturnType<typeof setTimeout>;
watch(productSearch, () => {
    clearTimeout(productDebounce);
    productDebounce = setTimeout(() => fetchProducts(true), 300);
});

// Load more products
function loadMore() {
    productPage.value++;
    fetchProducts(false);
}

// Open receipt preview
function openReceiptPreview() {
    showReceiptPreview.value = true;
}

// Confirm & proceed to payment
function confirmAndPay() {
    showReceiptPreview.value = false;
    openPaymentDialog();
}

// Open payment dialog
function openPaymentDialog() {
    amountTendered.value = total.value;
    referenceNumber.value = '';
    paymentMethod.value = 'cash';
    splitMode.value = false;
    splitPayments.value = [];
    showPaymentDialog.value = true;
}

// Quick amount buttons
function setQuickAmount(amount: number) {
    amountTendered.value = amount;
}

const quickAmounts = computed(() => {
    const t = total.value;
    const amounts: number[] = [];
    amounts.push(Math.ceil(t)); // exact
    const roundups = [50, 100, 200, 500, 1000];
    for (const r of roundups) {
        const rounded = Math.ceil(t / r) * r;
        if (rounded > t && !amounts.includes(rounded)) {
            amounts.push(rounded);
            if (amounts.length >= 4) break;
        }
    }
    return amounts;
});

// Checkout
async function processCheckout() {
    processing.value = true;
    try {
        const xsrfToken = decodeURIComponent(
            document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
        );

        const payload = {
            items: cart.value.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                variations: item.variations ?? [],
                addons: item.addons ?? [],
                notes: item.notes || null,
            })),
            customer_id: selectedCustomer.value?.id ?? null,
            payments: splitMode.value
                ? splitPayments.value.map(p => ({
                    method: p.method,
                    amount: p.amount,
                    reference_number: p.reference_number || null,
                }))
                : [{
                    method: paymentMethod.value,
                    amount: paymentMethod.value === 'cash' ? amountTendered.value : total.value,
                    reference_number: referenceNumber.value || null,
                }],
            discount_amount: discountAmount.value || null,
            discount_type: discountAmount.value ? discountType.value : null,
            notes: orderNotes.value || null,
            order_type: orderType.value,
            pos_operator_id: operatorUserId.value,
            table_id: selectedTable.value ?? null,
            promotion_id: appliedPromo.value?.id ?? null,
            discount_customer_name: discountCustomer.value?.name ?? null,
            discount_customer_id_number: discountCustomer.value?.id_number ?? null,
            discount_customer_birthday: discountCustomer.value?.birthday ?? null,
            order_id: recalledOrderId.value ?? null,
        };

        const res = await fetch(tenantUrl('pos/checkout'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok) {
            const err = await res.json();
            alert(err.message || 'Checkout failed. Please try again.');
            return;
        }

        const data = await res.json();
        completedOrder.value = data.order;
        showPaymentDialog.value = false;
        showReceiptDialog.value = true;
        clearCart();
        refreshSummary();
        fetchBillingHistory();

        // Auto-print receipt if branch setting is enabled
        nextTick(() => {
            if (props.branchSettings?.receipt_printing && completedReceiptData.value) {
                doPrintReceipt(completedReceiptData.value, true, {
                    logoUrl: tenant.value?.settings?.receipt_logo_url ?? null,
                    showAddress: tenant.value?.settings?.receipt_show_address !== false,
                    showPhone: tenant.value?.settings?.receipt_show_phone !== false,
                    showCustomer: tenant.value?.settings?.receipt_show_customer !== false,
                    showTable: tenant.value?.settings?.receipt_show_table !== false,
                    showOrderType: tenant.value?.settings?.receipt_show_order_type !== false,
                    showTaxBreakdown: tenant.value?.settings?.receipt_show_tax_breakdown !== false,
                    thankYouMessage: (tenant.value?.settings?.receipt_thank_you_message as string) ?? '',
                    width: (tenant.value?.settings?.receipt_width as '58mm' | '80mm') ?? '80mm',
                });
            }
            // Auto-print KOT if kitchen display is enabled
            if (props.branchSettings?.kitchen_display && data.order) {
                const o = data.order;
                const kotData: KotData = {
                    orderNumber: o.order_number,
                    dateTime: new Date(o.created_at).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }),
                    tableName: o.table?.name ?? null,
                    orderType: o.order_type === 'take_out' ? 'TAKE OUT' : 'DINE IN',
                    items: (o.items ?? []).map((item: any) => ({
                        name: item.product_name,
                        quantity: item.quantity,
                        variations: item.variations?.map((v: any) => ({
                            group_name: v.variation_group_name,
                            option_name: v.option_name,
                        })),
                        addons: item.item_addons?.map((a: any) => ({
                            name: a.addon_name,
                        })),
                    })),
                    notes: o.notes,
                };
                printKot(kotData);
            }
        });
    } catch {
        alert('Checkout failed. Please try again.');
    } finally {
        processing.value = false;
    }
}

// Receipt data for preview (before payment)
const previewReceiptData = computed<ReceiptData>(() => ({
    storeName: tenant.value?.settings?.store_name || tenant.value?.name || 'Store',
    storeAddress: tenant.value?.settings?.store_address,
    storePhone: tenant.value?.settings?.store_phone,
    receiptHeader: tenant.value?.settings?.receipt_header,
    receiptFooter: tenant.value?.settings?.receipt_footer,
    dateTime: new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }),
    cashier: operatorName.value || 'Cashier',
    customer: selectedCustomer.value?.name ?? null,
    items: cart.value.map(item => ({
        name: item.name,
        quantity: item.quantity,
        price: item.price,
        subtotal: item.price * item.quantity,
    })),
    subtotal: subtotal.value,
    discount: discountValue.value > 0 ? discountValue.value : undefined,
    promotionDiscount: promoDiscount.value > 0 ? promoDiscount.value : undefined,
    promotionCode: appliedPromo.value?.code ?? null,
    tax: taxAmount.value > 0 ? taxAmount.value : undefined,
    taxLabel: taxLabel.value,
    total: total.value,
    tableName: selectedTable.value ? props.tables.find(t => t.id === selectedTable.value)?.name : null,
    orderType: orderType.value === 'take_out' ? 'TAKE OUT' : 'DINE IN',
}));

// Receipt data for completed order (after payment)
const completedReceiptData = computed<ReceiptData>(() => {
    if (!completedOrder.value) return previewReceiptData.value;
    const o = completedOrder.value;
    const payment = o.payments?.[0];
    return {
        storeName: tenant.value?.settings?.store_name || tenant.value?.name || 'Store',
        storeAddress: tenant.value?.settings?.store_address,
        storePhone: tenant.value?.settings?.store_phone,
        receiptHeader: tenant.value?.settings?.receipt_header,
        receiptFooter: tenant.value?.settings?.receipt_footer,
        orderNumber: o.order_number,
        dateTime: new Date(o.created_at).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }),
        cashier: o.creator?.name ?? (operatorName.value || 'Cashier'),
        customer: o.customer?.name ?? null,
        items: (o.items ?? []).map((item: any) => ({
            name: item.product_name,
            quantity: item.quantity,
            price: Number(item.product_price),
            subtotal: Number(item.subtotal),
        })),
        subtotal: Number(o.subtotal),
        discount: Number(o.discount_amount) > 0 ? Number(o.discount_amount) : undefined,
        promotionDiscount: Number(o.promotion_discount) > 0 ? Number(o.promotion_discount) : undefined,
        promotionCode: o.promotion?.code ?? null,
        tax: Number(o.tax_amount) > 0 ? Number(o.tax_amount) : undefined,
        taxLabel: tenant.value?.settings?.tax_label || 'Tax',
        total: Number(o.total),
        tableName: o.table?.name ?? null,
        paymentMethod: payment?.method,
        amountTendered: payment?.amount_tendered ? Number(payment.amount_tendered) : undefined,
        change: payment?.change_amount ? Number(payment.change_amount) : undefined,
        referenceNumber: payment?.reference_number ?? null,
        orderType: o.order_type === 'take_out' ? 'TAKE OUT' : 'DINE IN',
    };
});

function printReceipt() {
    if (completedReceiptData.value) {
        doPrintReceipt(completedReceiptData.value, true, {
            logoUrl: tenant.value?.settings?.receipt_logo_url ?? null,
            showAddress: tenant.value?.settings?.receipt_show_address !== false,
            showPhone: tenant.value?.settings?.receipt_show_phone !== false,
            showCustomer: tenant.value?.settings?.receipt_show_customer !== false,
            showTable: tenant.value?.settings?.receipt_show_table !== false,
            showOrderType: tenant.value?.settings?.receipt_show_order_type !== false,
            showTaxBreakdown: tenant.value?.settings?.receipt_show_tax_breakdown !== false,
            thankYouMessage: (tenant.value?.settings?.receipt_thank_you_message as string) ?? '',
            width: (tenant.value?.settings?.receipt_width as '58mm' | '80mm') ?? '80mm',
        });
    }
}

function downloadPdf() {
    if (!completedOrder.value) return;
    window.open(tenantUrl(`orders/${completedOrder.value.id}/receipt/pdf`), '_blank');
}

function closeReceipt() {
    showReceiptDialog.value = false;
    completedOrder.value = null;
}

// Hold/Park Order state
const showHeldOrders = ref(false);
const heldOrders = ref<any[]>([]);
const loadingHeld = ref(false);
const holdingOrder = ref(false);
const recalledOrderId = ref<number | null>(null);

async function holdOrder() {
    if (cart.value.length === 0) return;
    holdingOrder.value = true;

    try {
        const xsrfToken = decodeURIComponent(
            document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
        );

        const payload = {
            items: cart.value.map(item => ({
                product_id: item.product_id,
                product_name: item.name,
                product_price: item.price,
                quantity: item.quantity,
                notes: item.notes || null,
                variations: item.variations ?? [],
                addons: item.addons ?? [],
            })),
            customer_id: selectedCustomer.value?.id ?? null,
            notes: orderNotes.value || null,
            order_type: orderType.value,
            table_id: selectedTable.value ?? null,
        };

        const res = await fetch(tenantUrl('pos/hold'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
            body: JSON.stringify(payload),
        });

        if (res.ok) {
            clearCart();
            recalledOrderId.value = null;
        } else {
            const err = await res.json();
            alert(err.message || 'Failed to hold order.');
        }
    } catch {
        alert('Failed to hold order.');
    } finally {
        holdingOrder.value = false;
    }
}

async function fetchHeldOrders() {
    loadingHeld.value = true;
    try {
        const res = await fetch(tenantUrl('pos/held-orders'), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        heldOrders.value = await res.json();
    } catch {
        heldOrders.value = [];
    } finally {
        loadingHeld.value = false;
    }
}

async function recallOrder(orderId: number) {
    try {
        const res = await fetch(tenantUrl(`pos/held-orders/${orderId}`), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();

        // Populate cart from recalled data
        cart.value = data.items.map((item: any) => {
            const variations = (item.variations ?? []).map((v: any) => ({
                variation_group_name: v.variation_group_name,
                option_name: v.option_name,
                price_modifier: v.price_modifier,
            }));
            const addons = (item.addons ?? []).map((a: any) => ({
                addon_name: a.addon_name,
                addon_price: a.addon_price,
            }));
            const variationTotal = variations.reduce((s: number, v: any) => s + v.price_modifier, 0);
            const addonTotal = addons.reduce((s: number, a: any) => s + a.addon_price, 0);
            const extras = [...variations.map((v: any) => v.option_name), ...addons.map((a: any) => a.addon_name)];
            const displayName = extras.length > 0 ? `${item.product_name} (${extras.join(', ')})` : item.product_name;

            return {
                product_id: item.product_id,
                name: displayName,
                price: item.product_price + variationTotal + addonTotal,
                quantity: item.quantity,
                notes: item.notes ?? '',
                variations,
                addons,
            };
        });

        if (data.customer) {
            selectedCustomer.value = data.customer;
        }
        if (data.notes) orderNotes.value = data.notes;
        if (data.order_type) orderType.value = data.order_type;
        if (data.table_id) selectedTable.value = data.table_id;
        recalledOrderId.value = data.order_id;
        showHeldOrders.value = false;
    } catch {
        alert('Failed to recall order.');
    }
}

async function deleteHeldOrder(orderId: number) {
    try {
        const xsrfToken = decodeURIComponent(
            document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
        );
        await fetch(tenantUrl(`pos/held-orders/${orderId}`), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
        });
        fetchHeldOrders();
    } catch { /* silently fail */ }
}

function openHeldOrders() {
    fetchHeldOrders();
    showHeldOrders.value = true;
}

function selectCustomer(customer: PosCustomer) {
    selectedCustomer.value = customer;
    customerSearch.value = '';
    customerResults.value = [];
    showCustomerSearch.value = false;
}

function removeCustomer() {
    selectedCustomer.value = null;
}

// Barcode input ref for programmatic focus
const barcodeInputRef = ref<InstanceType<typeof Input> | null>(null);

function focusBarcodeInput() {
    nextTick(() => {
        const el = barcodeInputRef.value?.$el?.querySelector('input') ?? barcodeInputRef.value?.$el;
        el?.focus();
    });
}

// Billing History state
interface BillingHistoryItem {
    id: number;
    order_number: string;
    customer_name: string;
    items_count: number;
    total: string;
    time: string;
}

const billingHistory = ref<BillingHistoryItem[]>([]);
const loadingBillingHistory = ref(false);

async function fetchBillingHistory() {
    loadingBillingHistory.value = true;
    try {
        const res = await fetch(tenantUrl('pos/billing-history'), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (res.ok) {
            billingHistory.value = await res.json();
        }
    } catch {
        // silently fail
    } finally {
        loadingBillingHistory.value = false;
    }
}

// Mobile responsiveness
const isMobile = ref(false);
const showMobileCart = ref(false);
const showBillingHistorySheet = ref(false);

function checkMobile() {
    isMobile.value = window.innerWidth < 768;
}

function openReceiptPreviewMobile() {
    showMobileCart.value = false;
    nextTick(() => openReceiptPreview());
}

onMounted(() => {
    fetchProducts(true);
    fetchBillingHistory();
    checkMobile();
    window.addEventListener('resize', checkMobile);
    // If already authenticated (module-level state persists across navigations), check shift
    if (isAuthenticated.value) {
        checkAndPromptShift();
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
});
</script>

<template>
    <PosLayout>
        <PosLoginModal v-if="!isAuthenticated" />
        <StartShiftDialog v-model:open="showStartShift" @shift-started="onShiftStarted" />
        <div class="flex h-full gap-2 p-2 md:gap-3 md:p-3 lg:gap-4 lg:p-4 overflow-hidden">
            <!-- Left Panel: Products + Billing History -->
            <div class="flex flex-1 flex-col gap-4 min-h-0">
                <Card class="flex flex-1 flex-col min-h-0 overflow-hidden py-0 gap-0">
                    <!-- Search bar + Barcode scanner -->
                    <div class="flex flex-col gap-2 border-b px-3 py-2.5 md:flex-row md:items-center md:gap-2 md:px-4 md:py-3">
                        <div class="relative flex-1">
                            <Search class="absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="productSearch" placeholder="Search products..." class="h-11 rounded-full bg-muted/50 border-none pl-10 text-sm" />
                        </div>
                        <div class="flex items-center gap-1.5 md:gap-2">
                            <button
                                @click="showImages = !showImages"
                                class="flex items-center gap-2 rounded-full border px-2.5 py-2 lg:px-4 lg:py-2.5 text-xs font-medium text-muted-foreground transition-colors hover:border-primary/40 hover:text-primary"
                                :title="showImages ? 'Switch to compact list' : 'Switch to image grid'"
                            >
                                <List v-if="showImages" class="h-4 w-4" />
                                <LayoutGrid v-else class="h-4 w-4" />
                                <span class="hidden lg:inline">{{ showImages ? 'Compact' : 'Grid' }}</span>
                            </button>
                            <button
                                @click="showBillingHistorySheet = true"
                                class="flex md:hidden items-center gap-2 rounded-full border px-2.5 py-2 text-xs font-medium text-muted-foreground transition-colors hover:border-primary/40 hover:text-primary"
                            >
                                <History class="h-4 w-4" />
                            </button>
                            <button
                                @click="focusBarcodeInput"
                                :class="[
                                    'flex items-center gap-2 rounded-full border px-2.5 py-2 lg:px-4 lg:py-2.5 text-xs font-medium transition-colors hover:border-primary/40 hover:text-primary',
                                    scanStatus === 'scanned' ? 'border-green-300 text-green-600 bg-green-50 dark:bg-green-950' : scanStatus === 'not_found' ? 'border-red-300 text-red-600 bg-red-50 dark:bg-red-950' : 'text-muted-foreground',
                                ]"
                            >
                                <ScanBarcode class="h-4 w-4" />
                                <span class="hidden lg:inline">Scan Barcode</span>
                            </button>
                            <button
                                @click="showDiscountModal = true"
                                :class="[
                                    'flex items-center gap-2 rounded-full border px-2.5 py-2 lg:px-4 lg:py-2.5 text-xs font-medium transition-colors hover:border-primary/40 hover:text-primary',
                                    hasActiveDiscount ? 'border-green-300 text-green-600 bg-green-50 dark:bg-green-950' : 'text-muted-foreground',
                                ]"
                            >
                                <Tag class="h-4 w-4" />
                                <span class="hidden lg:inline">Discounts</span>
                            </button>
                            <button
                                v-if="presetDiscounts && presetDiscounts.length > 0"
                                @click="showDiscountPanel = true"
                                :class="[
                                    'flex items-center gap-2 rounded-full border px-2.5 py-2 lg:px-4 lg:py-2.5 text-xs font-medium transition-colors hover:border-primary/40 hover:text-primary',
                                    appliedPresetDiscount ? 'border-green-300 text-green-600 bg-green-50 dark:bg-green-950' : 'text-muted-foreground',
                                ]"
                            >
                                <Tag class="h-4 w-4" />
                                <span class="hidden lg:inline">ID Discount</span>
                            </button>
                        </div>
                        <Input
                            ref="barcodeInputRef"
                            v-model="barcodeInput"
                            class="sr-only"
                            tabindex="-1"
                            @keydown.enter="handleBarcodeScan"
                        />
                    </div>

                    <!-- Category pills -->
                    <div class="relative flex items-center border-b">
                        <button
                            v-show="canScrollLeft"
                            @click="scrollCategories('left')"
                            class="absolute left-0 z-10 flex h-full w-8 items-center justify-center bg-gradient-to-r from-card via-card/90 to-transparent"
                        >
                            <ChevronLeft class="h-4 w-4 text-muted-foreground" />
                        </button>
                        <div
                            ref="categoryScrollRef"
                            class="flex gap-2 overflow-x-auto scrollbar-none px-4 py-2.5"
                            @scroll="updateCategoryScroll"
                        >
                            <button
                                @click="selectCategory(null)"
                                :class="[
                                    'shrink-0 rounded-full px-4 py-2 text-xs font-semibold transition-all',
                                    !selectedCategory ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border hover:border-primary/40 hover:text-primary',
                                ]"
                            >
                                All Products
                            </button>
                            <button
                                v-for="cat in categories"
                                :key="cat.id"
                                @click="selectCategory(cat.id)"
                                :class="[
                                    'shrink-0 rounded-full px-4 py-2 text-xs font-semibold transition-all',
                                    selectedCategory === cat.id ? 'bg-primary text-primary-foreground shadow-sm' : 'bg-card border hover:border-primary/40 hover:text-primary',
                                ]"
                            >
                                {{ cat.name }}
                            </button>
                        </div>
                        <button
                            v-show="canScrollRight"
                            @click="scrollCategories('right')"
                            class="absolute right-0 z-10 flex h-full w-8 items-center justify-center bg-gradient-to-l from-card via-card/90 to-transparent"
                        >
                            <ChevronRight class="h-4 w-4 text-muted-foreground" />
                        </button>
                    </div>

                    <!-- Product grid -->
                    <div class="flex-1 overflow-y-auto scrollbar-none p-3">
                        <div v-if="loadingProducts && products.length === 0" class="flex h-full items-center justify-center">
                            <p class="text-muted-foreground">Loading products...</p>
                        </div>
                        <div v-else-if="products.length === 0" class="flex h-full items-center justify-center">
                            <p class="text-muted-foreground">No products found</p>
                        </div>
                        <div v-else>
                            <div :class="[
                                'grid',
                                showImages
                                    ? 'grid-cols-3 gap-2 md:grid-cols-2 md:gap-2.5 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5'
                                    : 'grid-cols-2 gap-1.5 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6'
                            ]">
                                <button
                                    v-for="product in products"
                                    :key="product.id"
                                    @click="handleProductClick(product)"
                                    :class="[
                                        'text-left transition-all hover:ring-1 hover:ring-primary/20 active:scale-[0.98]',
                                        showImages
                                            ? 'flex flex-col rounded-xl bg-card p-2.5 shadow-sm hover:shadow-md'
                                            : 'flex flex-col justify-between rounded-lg bg-card px-2.5 py-2 shadow-sm hover:shadow-md'
                                    ]"
                                >
                                    <template v-if="showImages">
                                        <div class="aspect-square w-full overflow-hidden rounded-lg bg-muted mb-1.5">
                                            <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="h-full w-full object-cover" />
                                            <div v-else class="flex h-full items-center justify-center text-muted-foreground">
                                                <ShoppingCart class="h-6 w-6 opacity-30" />
                                            </div>
                                        </div>
                                        <p class="text-xs font-medium leading-tight line-clamp-2">{{ product.name }}</p>
                                        <div class="mt-auto pt-1.5">
                                            <span class="inline-block rounded-full bg-primary/10 text-primary px-2.5 py-0.5 text-xs font-bold">{{ formatCurrency(product.price) }}</span>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <p class="text-xs font-medium leading-tight line-clamp-2">{{ product.name }}</p>
                                        <span class="mt-1 inline-block rounded-full bg-primary/10 text-primary px-2 py-0.5 text-[11px] font-bold">{{ formatCurrency(product.price) }}</span>
                                    </template>
                                </button>
                            </div>
                            <div v-if="hasMoreProducts" class="mt-4 flex justify-center">
                                <Button variant="outline" size="sm" @click="loadMore" :disabled="loadingProducts">
                                    {{ loadingProducts ? 'Loading...' : 'Load More' }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Billing History Card -->
                <Card class="hidden md:block shrink-0 py-0 gap-0 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 border-b">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10">
                                <History class="h-3.5 w-3.5 text-primary" />
                            </div>
                            <h3 class="text-sm font-semibold">Billing History</h3>
                            <Badge variant="secondary" class="text-[10px]">Today</Badge>
                        </div>
                    </div>
                    <div class="max-h-[120px] lg:max-h-[180px] overflow-y-auto">
                        <div v-if="loadingBillingHistory" class="py-4 text-center text-xs text-muted-foreground">Loading...</div>
                        <div v-else-if="billingHistory.length === 0" class="py-4 text-center text-xs text-muted-foreground">No orders today</div>
                        <div v-else class="divide-y">
                            <div v-for="order in billingHistory" :key="order.id" class="flex items-center gap-3 px-4 py-2">
                                <Avatar class="h-8 w-8 shrink-0">
                                    <AvatarFallback class="bg-muted text-[10px] font-semibold">{{ order.customer_name.charAt(0).toUpperCase() }}</AvatarFallback>
                                </Avatar>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs font-medium truncate">{{ order.customer_name }}</p>
                                        <span class="text-[10px] text-muted-foreground">#{{ order.order_number }}</span>
                                    </div>
                                    <p class="text-[10px] text-muted-foreground">{{ order.items_count }} item{{ order.items_count !== 1 ? 's' : '' }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-xs font-semibold text-primary">{{ formatCurrency(order.total) }}</p>
                                    <p class="text-[10px] text-muted-foreground">{{ order.time }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Right Panel: Cart -->
            <Card class="hidden md:flex w-[280px] lg:w-[340px] xl:w-[400px] shrink-0 flex-col overflow-hidden py-0 gap-0">
                <!-- Cart header -->
                <div class="flex items-center justify-between border-b px-5 py-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                            <ShoppingCart class="h-4 w-4 text-primary" />
                        </div>
                        <h2 class="font-bold text-base">Cart</h2>
                        <Badge v-if="cartItemCount > 0" variant="secondary" class="text-xs">{{ cartItemCount }}</Badge>
                    </div>
                    <div class="flex items-center gap-1">
                        <Button variant="ghost" size="sm" class="text-xs text-muted-foreground relative" @click="openHeldOrders" title="Held Orders">
                            <Pause class="h-3 w-3" />
                            Held
                        </Button>
                        <Button v-if="cart.length > 0" variant="ghost" size="sm" class="text-xs text-muted-foreground" @click="clearCart">
                            Clear
                        </Button>
                    </div>
                </div>

                <!-- Customer section -->
                <div class="border-b px-5 py-2">
                    <div v-if="selectedCustomer" class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <User class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium">{{ selectedCustomer.name }}</p>
                                <p v-if="selectedCustomer.phone" class="text-xs text-muted-foreground">{{ selectedCustomer.phone }}</p>
                            </div>
                        </div>
                        <Button variant="ghost" size="icon" class="h-6 w-6" @click="removeCustomer">
                            <X class="h-3 w-3" />
                        </Button>
                    </div>
                    <div v-else class="relative">
                        <div class="flex items-center justify-between">
                            <button
                                @click="showCustomerSearch = !showCustomerSearch"
                                class="flex items-center gap-2 rounded-md py-1 text-sm text-muted-foreground hover:text-foreground transition-colors"
                            >
                                <User class="h-4 w-4" />
                                <span>Add customer (optional)</span>
                            </button>
                            <button
                                @click="openAddCustomer"
                                class="flex h-6 w-6 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                                title="Create new customer"
                            >
                                <UserPlus class="h-3.5 w-3.5" />
                            </button>
                        </div>
                        <div v-if="showCustomerSearch" class="absolute left-0 right-0 top-full z-10 mt-1 rounded-md border bg-popover p-2 shadow-md">
                            <Input v-model="customerSearch" placeholder="Search by name, email, phone..." class="text-sm" autofocus />
                            <div v-if="customerResults.length > 0" class="mt-1 max-h-40 overflow-y-auto">
                                <button
                                    v-for="c in customerResults"
                                    :key="c.id"
                                    @click="selectCustomer(c)"
                                    class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-muted"
                                >
                                    <User class="h-3 w-3 text-muted-foreground" />
                                    <span>{{ c.name }}</span>
                                    <span v-if="c.phone" class="text-xs text-muted-foreground">{{ c.phone }}</span>
                                </button>
                            </div>
                            <div v-else-if="customerSearch.length >= 2 && !searchingCustomers" class="py-2 text-center">
                                <p class="text-xs text-muted-foreground">No customers found</p>
                                <button
                                    @click="showCustomerSearch = false; openAddCustomer()"
                                    class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                                >
                                    <UserPlus class="h-3 w-3" />
                                    Create new customer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order type toggle -->
                <div v-if="cart.length > 0 && availableOrderTypes.length > 1" class="border-b px-5 py-2">
                    <div class="flex rounded-xl border p-0.5 gap-0.5">
                        <button
                            v-if="isEnabled('dine_in')"
                            @click="orderType = 'dine_in'"
                            :class="[
                                'flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors',
                                orderType === 'dine_in' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted',
                            ]"
                        >
                            <UtensilsCrossed class="h-3.5 w-3.5" />
                            Dine In
                        </button>
                        <button
                            v-if="isEnabled('takeout')"
                            @click="orderType = 'take_out'"
                            :class="[
                                'flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors',
                                orderType === 'take_out' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted',
                            ]"
                        >
                            <ShoppingBag class="h-3.5 w-3.5" />
                            Take Out
                        </button>
                    </div>
                </div>

                <!-- Table selection (dine-in only) -->
                <div v-if="cart.length > 0 && orderType === 'dine_in' && tables.length > 0" class="border-b px-5 py-2">
                    <p class="text-xs font-medium text-muted-foreground mb-1.5">Select Table</p>
                    <div class="grid grid-cols-4 gap-1.5">
                        <button
                            v-for="table in tables"
                            :key="table.id"
                            @click="selectedTable = selectedTable === table.id ? null : table.id"
                            :class="[
                                'rounded-md border px-2 py-1.5 text-xs font-medium transition-colors',
                                selectedTable === table.id
                                    ? 'border-primary bg-primary/10 text-primary shadow-sm'
                                    : 'hover:border-primary/50',
                            ]"
                        >
                            {{ table.name }}
                        </button>
                    </div>
                </div>

                <!-- Cart items -->
                <div class="flex-1 overflow-y-auto scrollbar-none">
                    <div v-if="cart.length === 0" class="flex h-full flex-col items-center justify-center text-muted-foreground">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/5 mb-3">
                            <ShoppingCart class="h-8 w-8 text-primary/30" />
                        </div>
                        <p class="text-sm font-medium">Cart is empty</p>
                        <p class="text-xs mt-0.5">Click products to add them</p>
                    </div>
                    <div v-else class="divide-y">
                        <div v-for="(item, index) in cart" :key="item.product_id" class="px-4 py-2.5">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-muted">
                                    <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="h-full w-full object-cover" />
                                    <div v-else class="flex h-full w-full items-center justify-center">
                                        <ShoppingCart class="h-4 w-4 opacity-30" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium leading-tight truncate">{{ item.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatCurrency(item.price) }}</p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <Button variant="outline" size="icon" class="h-7 w-7 rounded-full" @click="updateQuantity(index, -1)">
                                        <Minus class="h-3 w-3" />
                                    </Button>
                                    <span class="w-8 text-center text-sm font-semibold">{{ item.quantity }}</span>
                                    <Button variant="outline" size="icon" class="h-7 w-7 rounded-full" @click="updateQuantity(index, 1)">
                                        <Plus class="h-3 w-3" />
                                    </Button>
                                </div>
                                <p class="w-20 text-right text-sm font-semibold">{{ formatCurrency(item.price * item.quantity) }}</p>
                                <Button variant="ghost" size="icon" class="h-7 w-7 shrink-0 opacity-40 hover:opacity-100" @click="removeFromCart(index)">
                                    <Trash2 class="h-3 w-3 text-destructive" />
                                </Button>
                            </div>
                            <Input
                                v-model="item.notes"
                                placeholder="Item note (optional)"
                                class="mt-1 h-6 text-[11px] text-muted-foreground"
                            />
                        </div>
                    </div>
                </div>

                <!-- Applied discount/promo/notes badges -->
                <div v-if="cart.length > 0 && (hasActiveDiscount || orderNotes)" class="border-t px-5 py-2 space-y-1.5">
                    <!-- Manual discount badge -->
                    <div v-if="discountAmount > 0 && !appliedPresetDiscount && !appliedPromo" class="flex items-center justify-between rounded-md bg-green-50 dark:bg-green-950 px-2.5 py-1.5 border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <Percent class="h-3 w-3 text-green-600 dark:text-green-400 shrink-0" />
                            <span class="text-xs font-medium text-green-700 dark:text-green-300 truncate">
                                Manual Discount: {{ discountType === 'percentage' ? `${discountAmount}% off` : formatCurrency(discountAmount) + ' off' }}
                            </span>
                        </div>
                        <button class="ml-1 shrink-0 rounded p-0.5 hover:bg-green-100 dark:hover:bg-green-900" @click="discountAmount = 0">
                            <X class="h-3 w-3 text-green-600" />
                        </button>
                    </div>

                    <!-- Preset discount badge -->
                    <div v-if="appliedPresetDiscount" class="flex items-center justify-between rounded-md bg-green-50 dark:bg-green-950 px-2.5 py-1.5 border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <Tag class="h-3 w-3 text-green-600 dark:text-green-400 shrink-0" />
                            <span class="text-xs font-medium text-green-700 dark:text-green-300 truncate">
                                {{ appliedPresetDiscount.name }} &mdash; {{ discountCustomer?.name }} &mdash; -{{ formatCurrency(promoDiscount) }}
                            </span>
                        </div>
                        <button class="ml-1 shrink-0 rounded p-0.5 hover:bg-green-100 dark:hover:bg-green-900" @click="removePresetDiscount">
                            <X class="h-3 w-3 text-green-600" />
                        </button>
                    </div>

                    <!-- Promo code badge (non-preset) -->
                    <div v-else-if="appliedPromo" class="flex items-center justify-between rounded-md bg-green-50 dark:bg-green-950 px-2.5 py-1.5 border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <Tag class="h-3 w-3 text-green-600 dark:text-green-400 shrink-0" />
                            <span class="text-xs font-medium text-green-700 dark:text-green-300 truncate">
                                {{ appliedPromo.code }} &mdash; {{ appliedPromo.name }} &mdash; -{{ formatCurrency(promoDiscount) }}
                            </span>
                        </div>
                        <button class="ml-1 shrink-0 rounded p-0.5 hover:bg-green-100 dark:hover:bg-green-900" @click="removePromo">
                            <X class="h-3 w-3 text-green-600" />
                        </button>
                    </div>

                    <!-- Order notes indicator -->
                    <div v-if="orderNotes" class="flex items-center justify-between rounded-md bg-muted/50 px-2.5 py-1.5 border">
                        <p class="text-xs text-muted-foreground truncate mr-2">Note: {{ orderNotes }}</p>
                        <button class="shrink-0 rounded p-0.5 hover:bg-muted" @click="orderNotes = ''">
                            <X class="h-3 w-3 text-muted-foreground" />
                        </button>
                    </div>
                </div>

                <!-- Totals -->
                <div v-if="cart.length > 0" class="border-t px-5 py-3 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Subtotal</span>
                        <span>{{ formatCurrency(subtotal) }}</span>
                    </div>
                    <div v-if="discountValue > 0" class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>-{{ formatCurrency(discountValue) }}</span>
                    </div>
                    <div v-if="promoDiscount > 0" class="flex justify-between text-green-600">
                        <span>Promo ({{ appliedPromo?.code }})</span>
                        <span>-{{ formatCurrency(promoDiscount) }}</span>
                    </div>
                    <div v-if="taxAmount > 0" class="flex justify-between text-muted-foreground">
                        <span>{{ taxLabel }}{{ taxInclusive ? ' (incl.)' : '' }}</span>
                        <span>{{ formatCurrency(taxAmount) }}</span>
                    </div>
                    <Separator class="my-2" />
                    <div class="flex justify-between items-center">
                        <span class="text-xl font-bold">Grand Total</span>
                        <span class="text-xl font-bold text-primary">{{ formatCurrency(total) }}</span>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="border-t p-4 space-y-2">
                    <div v-if="cart.length > 0" class="flex gap-2">
                        <Button
                            variant="outline"
                            class="flex-1 h-10"
                            :disabled="holdingOrder"
                            @click="holdOrder"
                        >
                            <Pause class="mr-1.5 h-4 w-4" />
                            {{ holdingOrder ? 'Holding...' : 'Hold Order' }}
                        </Button>
                    </div>
                    <Button
                        class="w-full h-14 text-lg font-bold rounded-xl shadow-md"
                        :disabled="cart.length === 0"
                        @click="openReceiptPreview"
                    >
                        <ShoppingCart class="mr-2 h-5 w-5" />
                        Complete Purchase {{ cart.length > 0 ? formatCurrency(total) : '' }}
                    </Button>
                </div>
            </Card>

            <!-- Mobile floating cart button -->
            <button
                v-if="isMobile && cart.length > 0"
                @click="showMobileCart = true"
                class="fixed bottom-4 right-4 z-40 flex items-center gap-2 rounded-full bg-primary px-5 py-3.5 text-primary-foreground shadow-lg active:scale-95 transition-transform md:hidden"
            >
                <ShoppingCart class="h-5 w-5" />
                <span class="font-bold text-sm">{{ formatCurrency(total) }}</span>
                <span v-if="cartItemCount > 0" class="flex h-5 w-5 items-center justify-center rounded-full bg-white text-primary text-xs font-bold">{{ cartItemCount }}</span>
            </button>
        </div>

        <!-- Mobile Cart Sheet -->
        <Sheet v-model:open="showMobileCart">
            <SheetContent side="bottom" class="h-[85vh] p-0 flex flex-col rounded-t-2xl md:hidden">
                <SheetHeader class="px-5 pt-4 pb-2 border-b">
                    <SheetTitle class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                            <ShoppingCart class="h-4 w-4 text-primary" />
                        </div>
                        <span class="font-bold text-base">Cart</span>
                        <Badge v-if="cartItemCount > 0" variant="secondary" class="text-xs">{{ cartItemCount }}</Badge>
                    </SheetTitle>
                </SheetHeader>

                <!-- Customer section -->
                <div class="border-b px-5 py-2">
                    <div v-if="selectedCustomer" class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <User class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <p class="text-sm font-medium">{{ selectedCustomer.name }}</p>
                                <p v-if="selectedCustomer.phone" class="text-xs text-muted-foreground">{{ selectedCustomer.phone }}</p>
                            </div>
                        </div>
                        <Button variant="ghost" size="icon" class="h-6 w-6" @click="removeCustomer">
                            <X class="h-3 w-3" />
                        </Button>
                    </div>
                    <div v-else class="flex items-center justify-between">
                        <button
                            @click="showCustomerSearch = !showCustomerSearch"
                            class="flex items-center gap-2 rounded-md py-1 text-sm text-muted-foreground hover:text-foreground transition-colors"
                        >
                            <User class="h-4 w-4" />
                            <span>Add customer (optional)</span>
                        </button>
                        <button
                            @click="openAddCustomer"
                            class="flex h-6 w-6 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                            title="Create new customer"
                        >
                            <UserPlus class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Order type toggle -->
                <div v-if="cart.length > 0 && availableOrderTypes.length > 1" class="border-b px-5 py-2">
                    <div class="flex rounded-xl border p-0.5 gap-0.5">
                        <button
                            v-if="isEnabled('dine_in')"
                            @click="orderType = 'dine_in'"
                            :class="[
                                'flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors',
                                orderType === 'dine_in' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted',
                            ]"
                        >
                            <UtensilsCrossed class="h-3.5 w-3.5" />
                            Dine In
                        </button>
                        <button
                            v-if="isEnabled('takeout')"
                            @click="orderType = 'take_out'"
                            :class="[
                                'flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors',
                                orderType === 'take_out' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted',
                            ]"
                        >
                            <ShoppingBag class="h-3.5 w-3.5" />
                            Take Out
                        </button>
                    </div>
                </div>

                <!-- Table selection -->
                <div v-if="cart.length > 0 && orderType === 'dine_in' && tables.length > 0" class="border-b px-5 py-2">
                    <p class="text-xs font-medium text-muted-foreground mb-1.5">Select Table</p>
                    <div class="grid grid-cols-4 gap-1.5">
                        <button
                            v-for="table in tables"
                            :key="table.id"
                            @click="selectedTable = selectedTable === table.id ? null : table.id"
                            :class="[
                                'rounded-md border px-2 py-1.5 text-xs font-medium transition-colors',
                                selectedTable === table.id
                                    ? 'border-primary bg-primary/10 text-primary shadow-sm'
                                    : 'hover:border-primary/50',
                            ]"
                        >
                            {{ table.name }}
                        </button>
                    </div>
                </div>

                <!-- Cart items -->
                <div class="flex-1 overflow-y-auto scrollbar-none">
                    <div v-if="cart.length === 0" class="flex h-full flex-col items-center justify-center text-muted-foreground">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/5 mb-3">
                            <ShoppingCart class="h-8 w-8 text-primary/30" />
                        </div>
                        <p class="text-sm font-medium">Cart is empty</p>
                        <p class="text-xs mt-0.5">Click products to add them</p>
                    </div>
                    <div v-else class="divide-y">
                        <div v-for="(item, index) in cart" :key="item.product_id" class="px-4 py-2.5">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-muted">
                                    <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="h-full w-full object-cover" />
                                    <div v-else class="flex h-full w-full items-center justify-center">
                                        <ShoppingCart class="h-4 w-4 opacity-30" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium leading-tight truncate">{{ item.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatCurrency(item.price) }}</p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <Button variant="outline" size="icon" class="h-7 w-7 rounded-full" @click="updateQuantity(index, -1)">
                                        <Minus class="h-3 w-3" />
                                    </Button>
                                    <span class="w-8 text-center text-sm font-semibold">{{ item.quantity }}</span>
                                    <Button variant="outline" size="icon" class="h-7 w-7 rounded-full" @click="updateQuantity(index, 1)">
                                        <Plus class="h-3 w-3" />
                                    </Button>
                                </div>
                                <p class="w-20 text-right text-sm font-semibold">{{ formatCurrency(item.price * item.quantity) }}</p>
                                <Button variant="ghost" size="icon" class="h-7 w-7 shrink-0 opacity-40 hover:opacity-100" @click="removeFromCart(index)">
                                    <Trash2 class="h-3 w-3 text-destructive" />
                                </Button>
                            </div>
                            <Input
                                v-model="item.notes"
                                placeholder="Item note (optional)"
                                class="mt-1 h-6 text-[11px] text-muted-foreground"
                            />
                        </div>
                    </div>
                </div>

                <!-- Applied discount/promo/notes badges -->
                <div v-if="cart.length > 0 && (hasActiveDiscount || orderNotes)" class="border-t px-5 py-2 space-y-1.5">
                    <div v-if="discountAmount > 0 && !appliedPresetDiscount && !appliedPromo" class="flex items-center justify-between rounded-md bg-green-50 dark:bg-green-950 px-2.5 py-1.5 border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <Percent class="h-3 w-3 text-green-600 dark:text-green-400 shrink-0" />
                            <span class="text-xs font-medium text-green-700 dark:text-green-300 truncate">
                                Manual Discount: {{ discountType === 'percentage' ? `${discountAmount}% off` : formatCurrency(discountAmount) + ' off' }}
                            </span>
                        </div>
                        <button class="ml-1 shrink-0 rounded p-0.5 hover:bg-green-100 dark:hover:bg-green-900" @click="discountAmount = 0">
                            <X class="h-3 w-3 text-green-600" />
                        </button>
                    </div>
                    <div v-if="appliedPresetDiscount" class="flex items-center justify-between rounded-md bg-green-50 dark:bg-green-950 px-2.5 py-1.5 border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <Tag class="h-3 w-3 text-green-600 dark:text-green-400 shrink-0" />
                            <span class="text-xs font-medium text-green-700 dark:text-green-300 truncate">
                                {{ appliedPresetDiscount.name }} &mdash; {{ discountCustomer?.name }} &mdash; -{{ formatCurrency(promoDiscount) }}
                            </span>
                        </div>
                        <button class="ml-1 shrink-0 rounded p-0.5 hover:bg-green-100 dark:hover:bg-green-900" @click="removePresetDiscount">
                            <X class="h-3 w-3 text-green-600" />
                        </button>
                    </div>
                    <div v-else-if="appliedPromo" class="flex items-center justify-between rounded-md bg-green-50 dark:bg-green-950 px-2.5 py-1.5 border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <Tag class="h-3 w-3 text-green-600 dark:text-green-400 shrink-0" />
                            <span class="text-xs font-medium text-green-700 dark:text-green-300 truncate">
                                {{ appliedPromo.code }} &mdash; {{ appliedPromo.name }} &mdash; -{{ formatCurrency(promoDiscount) }}
                            </span>
                        </div>
                        <button class="ml-1 shrink-0 rounded p-0.5 hover:bg-green-100 dark:hover:bg-green-900" @click="removePromo">
                            <X class="h-3 w-3 text-green-600" />
                        </button>
                    </div>
                    <div v-if="orderNotes" class="flex items-center justify-between rounded-md bg-muted/50 px-2.5 py-1.5 border">
                        <p class="text-xs text-muted-foreground truncate mr-2">Note: {{ orderNotes }}</p>
                        <button class="shrink-0 rounded p-0.5 hover:bg-muted" @click="orderNotes = ''">
                            <X class="h-3 w-3 text-muted-foreground" />
                        </button>
                    </div>
                </div>

                <!-- Totals -->
                <div v-if="cart.length > 0" class="border-t px-5 py-3 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Subtotal</span>
                        <span>{{ formatCurrency(subtotal) }}</span>
                    </div>
                    <div v-if="discountValue > 0" class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>-{{ formatCurrency(discountValue) }}</span>
                    </div>
                    <div v-if="promoDiscount > 0" class="flex justify-between text-green-600">
                        <span>Promo ({{ appliedPromo?.code }})</span>
                        <span>-{{ formatCurrency(promoDiscount) }}</span>
                    </div>
                    <div v-if="taxAmount > 0" class="flex justify-between text-muted-foreground">
                        <span>{{ taxLabel }}{{ taxInclusive ? ' (incl.)' : '' }}</span>
                        <span>{{ formatCurrency(taxAmount) }}</span>
                    </div>
                    <Separator class="my-2" />
                    <div class="flex justify-between items-center">
                        <span class="text-xl font-bold">Grand Total</span>
                        <span class="text-xl font-bold text-primary">{{ formatCurrency(total) }}</span>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="border-t p-4 space-y-2">
                    <div v-if="cart.length > 0" class="flex gap-2">
                        <Button
                            variant="outline"
                            class="flex-1 h-10"
                            :disabled="holdingOrder"
                            @click="holdOrder"
                        >
                            <Pause class="mr-1.5 h-4 w-4" />
                            {{ holdingOrder ? 'Holding...' : 'Hold Order' }}
                        </Button>
                    </div>
                    <Button
                        class="w-full h-14 text-lg font-bold rounded-xl shadow-md"
                        :disabled="cart.length === 0"
                        @click="openReceiptPreviewMobile"
                    >
                        <ShoppingCart class="mr-2 h-5 w-5" />
                        Complete Purchase {{ cart.length > 0 ? formatCurrency(total) : '' }}
                    </Button>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Mobile Billing History Sheet -->
        <Sheet v-model:open="showBillingHistorySheet">
            <SheetContent side="bottom" class="h-[50vh] p-0 flex flex-col rounded-t-2xl md:hidden">
                <SheetHeader class="px-4 pt-4 pb-2 border-b">
                    <SheetTitle class="flex items-center gap-2 text-sm">
                        <History class="h-4 w-4" />
                        Billing History
                        <Badge variant="secondary" class="text-[10px]">Today</Badge>
                    </SheetTitle>
                </SheetHeader>
                <div class="flex-1 overflow-y-auto">
                    <div v-if="loadingBillingHistory" class="py-4 text-center text-xs text-muted-foreground">Loading...</div>
                    <div v-else-if="billingHistory.length === 0" class="py-4 text-center text-xs text-muted-foreground">No orders today</div>
                    <div v-else class="divide-y">
                        <div v-for="order in billingHistory" :key="order.id" class="flex items-center gap-3 px-4 py-2">
                            <Avatar class="h-8 w-8 shrink-0">
                                <AvatarFallback class="bg-muted text-[10px] font-semibold">{{ order.customer_name.charAt(0).toUpperCase() }}</AvatarFallback>
                            </Avatar>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-xs font-medium truncate">{{ order.customer_name }}</p>
                                    <span class="text-[10px] text-muted-foreground">#{{ order.order_number }}</span>
                                </div>
                                <p class="text-[10px] text-muted-foreground">{{ order.items_count }} item{{ order.items_count !== 1 ? 's' : '' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-semibold text-primary">{{ formatCurrency(order.total) }}</p>
                                <p class="text-[10px] text-muted-foreground">{{ order.time }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Receipt Preview Dialog -->
        <Dialog v-model:open="showReceiptPreview">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Receipt class="h-5 w-5" />
                        Order Preview
                    </DialogTitle>
                </DialogHeader>

                <div class="max-h-[60vh] overflow-y-auto py-2">
                    <ReceiptTemplate
                        :data="previewReceiptData"
                        :showPaymentDetails="false"
                        :logo-url="tenant?.settings?.receipt_logo_url ?? null"
                        :show-address="tenant?.settings?.receipt_show_address !== false"
                        :show-phone="tenant?.settings?.receipt_show_phone !== false"
                        :show-customer="tenant?.settings?.receipt_show_customer !== false"
                        :show-table="tenant?.settings?.receipt_show_table !== false"
                        :show-order-type="tenant?.settings?.receipt_show_order_type !== false"
                        :show-tax-breakdown="tenant?.settings?.receipt_show_tax_breakdown !== false"
                        :thank-you-message="(tenant?.settings?.receipt_thank_you_message as string) ?? ''"
                        :width="(tenant?.settings?.receipt_width as '58mm' | '80mm') ?? '80mm'"
                    />
                </div>

                <DialogFooter class="flex-row gap-2 sm:flex-row">
                    <Button variant="outline" class="flex-1" @click="showReceiptPreview = false">Back</Button>
                    <Button class="flex-1" @click="confirmAndPay">Confirm & Pay</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Payment Dialog -->
        <Dialog v-model:open="showPaymentDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Payment</DialogTitle>
                </DialogHeader>

                <div class="space-y-4">
                    <!-- Total display -->
                    <div class="rounded-lg bg-muted p-4 text-center">
                        <p class="text-sm text-muted-foreground">Total</p>
                        <p class="text-3xl font-bold">{{ formatCurrency(total) }}</p>
                    </div>

                    <!-- Split toggle -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">Split Payment</span>
                        <Button variant="outline" size="sm" class="text-xs" @click="toggleSplitMode">
                            {{ splitMode ? 'Single Payment' : 'Split Payment' }}
                        </Button>
                    </div>

                    <!-- Single Payment Mode -->
                    <template v-if="!splitMode">
                        <div class="grid grid-cols-4 gap-2">
                            <button
                                v-for="method in [
                                    { value: 'cash', label: 'Cash' },
                                    { value: 'card', label: 'Card' },
                                    { value: 'e_wallet', label: 'E-Wallet' },
                                ]"
                                :key="method.value"
                                @click="paymentMethod = method.value"
                                :class="[
                                    'rounded-lg border-2 px-3 py-2.5 text-sm font-medium transition-colors',
                                    paymentMethod === method.value ? 'border-primary bg-primary/5 text-primary' : 'border-muted hover:border-muted-foreground/30',
                                ]"
                            >
                                {{ method.label }}
                            </button>
                            <button
                                disabled
                                class="relative rounded-lg border-2 border-muted px-3 py-2.5 text-sm font-medium text-muted-foreground opacity-60 cursor-not-allowed"
                            >
                                Online
                                <span class="absolute -top-2 -right-2 rounded-full bg-orange-500 px-1.5 py-0.5 text-[10px] font-bold text-white leading-none">Soon</span>
                            </button>
                        </div>

                        <div v-if="paymentMethod === 'cash'" class="space-y-3">
                            <div class="space-y-2">
                                <Label>Amount Tendered</Label>
                                <Input v-model.number="amountTendered" type="number" min="0" step="0.01" class="text-lg h-12 text-center font-bold" />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button v-for="amount in quickAmounts" :key="amount" variant="outline" size="sm" @click="setQuickAmount(amount)">
                                    {{ formatCurrency(amount) }}
                                </Button>
                            </div>
                            <div v-if="amountTendered >= total" class="rounded-lg bg-green-50 dark:bg-green-950 p-3 text-center">
                                <p class="text-sm text-green-600 dark:text-green-400">Change</p>
                                <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ formatCurrency(changeAmount) }}</p>
                            </div>
                        </div>

                        <div v-else class="space-y-2">
                            <Label>Reference Number</Label>
                            <Input v-model="referenceNumber" placeholder="Reference Number" />
                        </div>
                    </template>

                    <!-- Split Payment Mode -->
                    <template v-else>
                        <div class="space-y-3">
                            <div v-for="(sp, idx) in splitPayments" :key="idx" class="rounded-lg border p-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-muted-foreground">Payment {{ idx + 1 }}</span>
                                    <Button v-if="splitPayments.length > 1" variant="ghost" size="icon" class="h-6 w-6" @click="removeSplitPayment(idx)">
                                        <X class="h-3 w-3" />
                                    </Button>
                                </div>
                                <div class="grid grid-cols-3 gap-1">
                                    <button
                                        v-for="m in [{ v: 'cash', l: 'Cash' }, { v: 'card', l: 'Card' }, { v: 'e_wallet', l: 'E-Wallet' }]"
                                        :key="m.v"
                                        @click="sp.method = m.v"
                                        :class="['rounded border px-2 py-1 text-xs', sp.method === m.v ? 'border-primary bg-primary/10 text-primary' : '']"
                                    >{{ m.l }}</button>
                                </div>
                                <Input v-model.number="sp.amount" type="number" min="0" step="0.01" placeholder="Amount" class="h-9 text-sm" />
                                <Input v-if="sp.method !== 'cash'" v-model="sp.reference_number" placeholder="Ref #" class="h-8 text-xs" />
                            </div>
                            <Button variant="outline" size="sm" class="w-full text-xs" @click="addSplitPayment">
                                <Plus class="mr-1 h-3 w-3" /> Add Payment
                            </Button>
                            <div v-if="splitRemaining > 0" class="text-sm text-center text-destructive">
                                Remaining: {{ formatCurrency(splitRemaining) }}
                            </div>
                            <div v-else class="text-sm text-center text-green-600">
                                Fully covered
                            </div>
                        </div>
                    </template>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showPaymentDialog = false">Cancel</Button>
                    <Button :disabled="!canCheckout || processing" class="min-w-[140px]" @click="processCheckout">
                        {{ processing ? 'Processing...' : 'Complete Order' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Receipt Dialog -->
        <Dialog v-model:open="showReceiptDialog">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Receipt class="h-5 w-5" />
                        Order completed successfully!
                    </DialogTitle>
                </DialogHeader>

                <div v-if="completedOrder" id="receipt-print-area" class="max-h-[60vh] overflow-y-auto py-2">
                    <ReceiptTemplate
                        :data="completedReceiptData"
                        :showPaymentDetails="true"
                        :logo-url="tenant?.settings?.receipt_logo_url ?? null"
                        :show-address="tenant?.settings?.receipt_show_address !== false"
                        :show-phone="tenant?.settings?.receipt_show_phone !== false"
                        :show-customer="tenant?.settings?.receipt_show_customer !== false"
                        :show-table="tenant?.settings?.receipt_show_table !== false"
                        :show-order-type="tenant?.settings?.receipt_show_order_type !== false"
                        :show-tax-breakdown="tenant?.settings?.receipt_show_tax_breakdown !== false"
                        :thank-you-message="(tenant?.settings?.receipt_thank_you_message as string) ?? ''"
                        :width="(tenant?.settings?.receipt_width as '58mm' | '80mm') ?? '80mm'"
                    />
                </div>

                <DialogFooter class="flex-col gap-2 sm:flex-col">
                    <div class="flex gap-2 w-full">
                        <Button variant="outline" class="flex-1" @click="printReceipt">
                            <Printer class="mr-2 h-4 w-4" />
                            Print Receipt
                        </Button>
                        <Button variant="outline" class="flex-1" @click="downloadPdf">
                            <Download class="mr-2 h-4 w-4" />
                            Download PDF
                        </Button>
                    </div>
                    <Button class="w-full" @click="closeReceipt">New Order</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <!-- Variation/Addon Modal -->
        <Dialog v-model:open="showVariationModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ variationProduct?.name }} - Options</DialogTitle>
                </DialogHeader>

                <div v-if="variationProduct" class="space-y-4 max-h-[60vh] overflow-y-auto">
                    <!-- Variation Groups -->
                    <div v-for="group in variationProduct.variation_groups" :key="group.id" class="space-y-2">
                        <Label class="text-sm font-semibold">
                            {{ group.name }}
                            <span v-if="group.is_required" class="text-destructive">*</span>
                        </Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="option in group.options"
                                :key="option.id"
                                type="button"
                                @click="selectedVariations[group.id] = { group_name: group.name, option_name: option.name, price_modifier: Number(option.price_modifier) }"
                                :class="[
                                    'rounded-lg border px-3 py-1.5 text-sm transition-colors',
                                    selectedVariations[group.id]?.option_name === option.name
                                        ? 'border-primary bg-primary/10 text-primary font-medium'
                                        : 'hover:border-primary/50',
                                ]"
                            >
                                {{ option.name }}
                                <span v-if="Number(option.price_modifier) > 0" class="text-xs text-muted-foreground">
                                    +{{ Number(option.price_modifier).toFixed(2) }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Add-ons -->
                    <div v-if="variationProduct.addons && variationProduct.addons.length > 0" class="space-y-2">
                        <Label class="text-sm font-semibold">Add-ons</Label>
                        <div class="space-y-1">
                            <label v-for="addon in variationProduct.addons" :key="addon.id" class="flex items-center gap-2 rounded-md border px-3 py-2 cursor-pointer hover:bg-muted/50">
                                <input type="checkbox" :checked="selectedAddons[addon.id]" @change="selectedAddons[addon.id] = !selectedAddons[addon.id]" class="rounded" />
                                <span class="flex-1 text-sm">{{ addon.name }}</span>
                                <span class="text-sm text-muted-foreground">+{{ Number(addon.price).toFixed(2) }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showVariationModal = false">Cancel</Button>
                    <Button @click="confirmVariationSelection">Add to Cart</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <!-- Held Orders Dialog -->
        <Dialog v-model:open="showHeldOrders">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Pause class="h-5 w-5" />
                        Held Orders ({{ heldOrders.length }})
                    </DialogTitle>
                </DialogHeader>
                <div class="max-h-[60vh] overflow-y-auto">
                    <div v-if="loadingHeld" class="py-8 text-center text-muted-foreground text-sm">Loading...</div>
                    <div v-else-if="heldOrders.length === 0" class="py-8 text-center text-muted-foreground text-sm">No held orders</div>
                    <div v-else class="divide-y">
                        <div v-for="held in heldOrders" :key="held.id" class="py-3 px-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-medium text-sm">{{ held.order_number }}</span>
                                <span class="text-xs text-muted-foreground">{{ new Date(held.held_at).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }) }}</span>
                            </div>
                            <div class="text-xs text-muted-foreground mb-2">
                                {{ held.items?.length ?? 0 }} items
                                <span v-if="held.customer"> &mdash; {{ held.customer.name }}</span>
                            </div>
                            <div class="flex gap-2">
                                <Button size="sm" variant="default" class="flex-1 text-xs" @click="recallOrder(held.id)">
                                    <Play class="mr-1 h-3 w-3" />
                                    Recall
                                </Button>
                                <Button size="sm" variant="destructive" class="text-xs" @click="deleteHeldOrder(held.id)">
                                    <Trash2 class="h-3 w-3" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
        <!-- Discount Panel -->
        <DiscountPanel
            v-model:open="showDiscountPanel"
            :preset-discounts="presetDiscounts ?? []"
            :subtotal="afterDiscount"
            @apply="applyPresetDiscount"
        />

        <!-- Discount & Promo Modal -->
        <Dialog v-model:open="showDiscountModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Tag class="h-5 w-5" />
                        Discounts & Promos
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <!-- When promo/preset already applied: show badge with remove -->
                    <div v-if="appliedPresetDiscount" class="flex items-center justify-between rounded-lg bg-green-50 dark:bg-green-950 px-3 py-3 border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2 min-w-0">
                            <Tag class="h-4 w-4 text-green-600 dark:text-green-400 shrink-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-green-700 dark:text-green-300">{{ appliedPresetDiscount.name }}</p>
                                <p class="text-xs text-green-600 dark:text-green-400 truncate">
                                    {{ discountCustomer?.name }} &mdash; -{{ formatCurrency(promoDiscount) }}
                                </p>
                            </div>
                        </div>
                        <Button variant="ghost" size="sm" class="shrink-0 text-xs text-green-600 hover:bg-green-100 dark:hover:bg-green-900" @click="removePresetDiscount">
                            Remove
                        </Button>
                    </div>

                    <div v-else-if="appliedPromo" class="flex items-center justify-between rounded-lg bg-green-50 dark:bg-green-950 px-3 py-3 border border-green-200 dark:border-green-800">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-green-700 dark:text-green-300">{{ appliedPromo.code }}</p>
                            <p class="text-xs text-green-600 dark:text-green-400">{{ appliedPromo.name }} &mdash; -{{ formatCurrency(promoDiscount) }}</p>
                        </div>
                        <Button variant="ghost" size="sm" class="shrink-0 text-xs text-green-600 hover:bg-green-100 dark:hover:bg-green-900" @click="removePromo">
                            Remove
                        </Button>
                    </div>

                    <!-- When nothing applied: show inputs -->
                    <template v-else>
                        <!-- Manual discount -->
                        <div v-if="operatorCan('pos.discount') && isEnabled('discounts_enabled')" class="space-y-2">
                            <Label class="text-sm font-medium">Manual Discount</Label>
                            <div class="flex items-center gap-2">
                                <Select v-model="discountType">
                                    <SelectTrigger class="w-[110px] h-9 text-xs">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="percentage">
                                            <span class="flex items-center gap-1"><Percent class="h-3 w-3" /> Percent</span>
                                        </SelectItem>
                                        <SelectItem value="fixed">
                                            <span class="flex items-center gap-1"><DollarSign class="h-3 w-3" /> Fixed</span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Input
                                    v-model.number="discountAmount"
                                    type="number"
                                    min="0"
                                    :max="discountType === 'percentage' ? 100 : subtotal"
                                    placeholder="0"
                                    class="h-9 text-sm"
                                />
                            </div>
                        </div>

                        <Separator />

                        <!-- Promo code -->
                        <div class="space-y-2">
                            <Label class="text-sm font-medium">Promo Code</Label>
                            <div class="flex items-center gap-2">
                                <Input
                                    v-model="promoCode"
                                    placeholder="Enter promo code"
                                    class="h-9 text-sm uppercase flex-1"
                                    @keydown.enter="applyPromoCode"
                                />
                                <Button variant="outline" size="sm" class="h-9 shrink-0 text-xs" :disabled="promoLoading || !promoCode.trim()" @click="applyPromoCode">
                                    {{ promoLoading ? '...' : 'Apply' }}
                                </Button>
                            </div>
                            <p v-if="promoError" class="text-xs text-destructive">{{ promoError }}</p>
                        </div>

                    </template>

                    <Separator />

                    <!-- Order notes (always visible) -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Order Notes</Label>
                        <Textarea v-model="orderNotes" placeholder="Add notes for this order..." rows="2" class="resize-none text-sm" />
                    </div>
                </div>

                <DialogFooter>
                    <Button class="w-full" @click="showDiscountModal = false">Done</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Add Customer Modal -->
        <Dialog v-model:open="showAddCustomer">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>New Customer</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="saveNewCustomer" class="flex flex-col gap-4 py-2">
                    <div class="space-y-1.5">
                        <Label for="new-cust-name">Name <span class="text-destructive">*</span></Label>
                        <Input
                            id="new-cust-name"
                            v-model="newCustomer.name"
                            placeholder="Customer name"
                            :class="{ 'border-destructive': newCustomerErrors.name }"
                            autofocus
                        />
                        <p v-if="newCustomerErrors.name" class="text-xs text-destructive">{{ newCustomerErrors.name }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="new-cust-email">Email</Label>
                        <Input
                            id="new-cust-email"
                            v-model="newCustomer.email"
                            type="email"
                            placeholder="email@example.com"
                            :class="{ 'border-destructive': newCustomerErrors.email }"
                        />
                        <p v-if="newCustomerErrors.email" class="text-xs text-destructive">{{ newCustomerErrors.email }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="new-cust-phone">Phone</Label>
                        <Input
                            id="new-cust-phone"
                            v-model="newCustomer.phone"
                            placeholder="+63 912 345 6789"
                            :class="{ 'border-destructive': newCustomerErrors.phone }"
                        />
                        <p v-if="newCustomerErrors.phone" class="text-xs text-destructive">{{ newCustomerErrors.phone }}</p>
                    </div>
                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button variant="outline" type="button" @click="showAddCustomer = false">Cancel</Button>
                        <Button type="submit" :disabled="savingCustomer">
                            {{ savingCustomer ? 'Saving...' : 'Save Customer' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </PosLayout>
</template>
