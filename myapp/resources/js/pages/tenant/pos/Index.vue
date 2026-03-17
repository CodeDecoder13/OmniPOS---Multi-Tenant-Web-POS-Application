<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { Minus, Plus, Search, ShoppingCart, Trash2, User, X, Receipt, Percent, DollarSign, Printer, Download, UtensilsCrossed, ShoppingBag } from 'lucide-vue-next';
import PosLayout from '@/layouts/PosLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import PosLoginModal from '@/components/PosLoginModal.vue';
import StartShiftDialog from '@/components/StartShiftDialog.vue';
import ReceiptTemplate from '@/components/ReceiptTemplate.vue';
import type { ReceiptData } from '@/components/ReceiptTemplate.vue';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import { usePosAuth } from '@/composables/usePosAuth';
import { usePosShift } from '@/composables/usePosShift';
import type { Category, Product } from '@/types';

interface CartItem {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
    image_url?: string | null;
}

interface PosCustomer {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
}

const props = defineProps<{
    categories: Pick<Category, 'id' | 'name'>[];
}>();

const { tenantUrl, tenant } = useTenant();
const { can } = usePermissions();
const { isAuthenticated, operatorCan, operatorUserId, operatorName } = usePosAuth();
const { hasActiveShift, checkShiftStatus, refreshSummary, clearShift } = usePosShift();

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
const products = ref<Product[]>([]);
const productSearch = ref('');
const selectedCategory = ref<number | null>(null);
const loadingProducts = ref(false);
const productPage = ref(1);
const hasMoreProducts = ref(false);

// Cart state
const cart = ref<CartItem[]>([]);
const orderType = ref<'dine_in' | 'take_out'>('dine_in');
const discountAmount = ref<number>(0);
const discountType = ref<'percentage' | 'fixed'>('percentage');
const orderNotes = ref('');

// Customer state
const selectedCustomer = ref<PosCustomer | null>(null);
const customerSearch = ref('');
const customerResults = ref<PosCustomer[]>([]);
const showCustomerSearch = ref(false);
const searchingCustomers = ref(false);

// Payment dialog state
const showPaymentDialog = ref(false);
const paymentMethod = ref('cash');
const amountTendered = ref<number>(0);
const referenceNumber = ref('');
const processing = ref(false);

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

const taxRate = computed(() => Number(tenant.value?.settings?.tax_rate) || 0);
const taxInclusive = computed(() => tenant.value?.settings?.tax_inclusive ?? false);
const taxLabel = computed(() => tenant.value?.settings?.tax_label || 'Tax');

const taxAmount = computed(() => {
    if (taxRate.value <= 0) return 0;
    if (taxInclusive.value) {
        return Math.round((afterDiscount.value - afterDiscount.value / (1 + taxRate.value / 100)) * 100) / 100;
    }
    return Math.round(afterDiscount.value * (taxRate.value / 100) * 100) / 100;
});

const total = computed(() => {
    if (taxInclusive.value || taxRate.value <= 0) return afterDiscount.value;
    return afterDiscount.value + taxAmount.value;
});
const changeAmount = computed(() => paymentMethod.value === 'cash' ? Math.max(0, amountTendered.value - total.value) : 0);
const canCheckout = computed(() => {
    if (cart.value.length === 0) return false;
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
    orderType.value = 'dine_in';
    discountAmount.value = 0;
    orderNotes.value = '';
    selectedCustomer.value = null;
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
            items: cart.value.map(item => ({ product_id: item.product_id, quantity: item.quantity })),
            customer_id: selectedCustomer.value?.id ?? null,
            payment_method: paymentMethod.value,
            amount_tendered: paymentMethod.value === 'cash' ? amountTendered.value : null,
            reference_number: referenceNumber.value || null,
            discount_amount: discountAmount.value || null,
            discount_type: discountAmount.value ? discountType.value : null,
            notes: orderNotes.value || null,
            order_type: orderType.value,
            pos_operator_id: operatorUserId.value,
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
    tax: taxAmount.value > 0 ? taxAmount.value : undefined,
    taxLabel: taxLabel.value,
    total: total.value,
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
        tax: Number(o.tax_amount) > 0 ? Number(o.tax_amount) : undefined,
        taxLabel: tenant.value?.settings?.tax_label || 'Tax',
        total: Number(o.total),
        paymentMethod: payment?.method,
        amountTendered: payment?.amount_tendered ? Number(payment.amount_tendered) : undefined,
        change: payment?.change_amount ? Number(payment.change_amount) : undefined,
        referenceNumber: payment?.reference_number ?? null,
        orderType: o.order_type === 'take_out' ? 'TAKE OUT' : 'DINE IN',
    };
});

function printReceipt() {
    window.print();
}

function downloadPdf() {
    if (!completedOrder.value) return;
    window.open(tenantUrl(`orders/${completedOrder.value.id}/receipt/pdf`), '_blank');
}

function closeReceipt() {
    showReceiptDialog.value = false;
    completedOrder.value = null;
}

function formatCurrency(amount: number | string) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount));
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

onMounted(() => {
    fetchProducts(true);
    // If already authenticated (module-level state persists across navigations), check shift
    if (isAuthenticated.value) {
        checkAndPromptShift();
    }
});
</script>

<template>
    <PosLayout>
        <PosLoginModal v-if="!isAuthenticated" />
        <StartShiftDialog v-model:open="showStartShift" @shift-started="onShiftStarted" />
        <div class="flex h-full">
            <!-- Left Panel: Products -->
            <div class="flex flex-1 flex-col border-r">
                <!-- Search bar -->
                <div class="flex items-center gap-2 border-b p-3">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="productSearch" placeholder="Search products..." class="pl-9" />
                    </div>
                </div>

                <!-- Category pills -->
                <div class="flex gap-2 overflow-x-auto border-b px-3 py-2 scrollbar-none">
                    <button
                        @click="selectCategory(null)"
                        :class="[
                            'shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                            !selectedCategory ? 'bg-primary text-primary-foreground' : 'bg-muted hover:bg-muted/80',
                        ]"
                    >
                        All
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        @click="selectCategory(cat.id)"
                        :class="[
                            'shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                            selectedCategory === cat.id ? 'bg-primary text-primary-foreground' : 'bg-muted hover:bg-muted/80',
                        ]"
                    >
                        {{ cat.name }}
                    </button>
                </div>

                <!-- Product grid -->
                <div class="flex-1 overflow-y-auto p-3">
                    <div v-if="loadingProducts && products.length === 0" class="flex h-full items-center justify-center">
                        <p class="text-muted-foreground">Loading products...</p>
                    </div>
                    <div v-else-if="products.length === 0" class="flex h-full items-center justify-center">
                        <p class="text-muted-foreground">No products found.</p>
                    </div>
                    <div v-else>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                            <button
                                v-for="product in products"
                                :key="product.id"
                                @click="addToCart(product)"
                                class="flex flex-col rounded-lg border bg-card p-2 text-left transition-all hover:shadow-md hover:border-primary/50 active:scale-[0.98]"
                            >
                                <div class="aspect-square w-full overflow-hidden rounded-md bg-muted mb-2">
                                    <img
                                        v-if="product.image_url"
                                        :src="product.image_url"
                                        :alt="product.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="flex h-full items-center justify-center text-muted-foreground">
                                        <ShoppingCart class="h-8 w-8 opacity-30" />
                                    </div>
                                </div>
                                <p class="text-xs font-medium leading-tight line-clamp-2">{{ product.name }}</p>
                                <p class="mt-auto pt-1 text-sm font-bold text-primary">{{ formatCurrency(product.price) }}</p>
                            </button>
                        </div>
                        <div v-if="hasMoreProducts" class="mt-4 flex justify-center">
                            <Button variant="outline" size="sm" @click="loadMore" :disabled="loadingProducts">
                                {{ loadingProducts ? 'Loading...' : 'Load More' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Cart -->
            <div class="flex w-[380px] flex-col bg-card">
                <!-- Cart header -->
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <div class="flex items-center gap-2">
                        <ShoppingCart class="h-4 w-4" />
                        <h2 class="font-semibold text-sm">Cart</h2>
                        <Badge v-if="cartItemCount > 0" variant="secondary" class="text-xs">{{ cartItemCount }}</Badge>
                    </div>
                    <Button v-if="cart.length > 0" variant="ghost" size="sm" class="text-xs text-muted-foreground" @click="clearCart">
                        Clear
                    </Button>
                </div>

                <!-- Customer section -->
                <div class="border-b px-4 py-2">
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
                        <button
                            @click="showCustomerSearch = !showCustomerSearch"
                            class="flex w-full items-center gap-2 rounded-md py-1 text-sm text-muted-foreground hover:text-foreground transition-colors"
                        >
                            <User class="h-4 w-4" />
                            <span>Add customer (optional)</span>
                        </button>
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
                            <p v-else-if="customerSearch.length >= 2 && !searchingCustomers" class="py-2 text-center text-xs text-muted-foreground">
                                No customers found
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Order type toggle -->
                <div v-if="cart.length > 0" class="border-b px-4 py-2">
                    <div class="flex rounded-lg border p-0.5 gap-0.5">
                        <button
                            @click="orderType = 'dine_in'"
                            :class="[
                                'flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                                orderType === 'dine_in' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted',
                            ]"
                        >
                            <UtensilsCrossed class="h-3.5 w-3.5" />
                            Dine In
                        </button>
                        <button
                            @click="orderType = 'take_out'"
                            :class="[
                                'flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                                orderType === 'take_out' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted',
                            ]"
                        >
                            <ShoppingBag class="h-3.5 w-3.5" />
                            Take Out
                        </button>
                    </div>
                </div>

                <!-- Cart items -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="cart.length === 0" class="flex h-full flex-col items-center justify-center text-muted-foreground">
                        <ShoppingCart class="h-12 w-12 opacity-20 mb-2" />
                        <p class="text-sm">Cart is empty</p>
                        <p class="text-xs">Click products to add them</p>
                    </div>
                    <div v-else class="divide-y">
                        <div v-for="(item, index) in cart" :key="item.product_id" class="flex items-center gap-3 px-4 py-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium leading-tight truncate">{{ item.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatCurrency(item.price) }}</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <Button variant="outline" size="icon" class="h-7 w-7" @click="updateQuantity(index, -1)">
                                    <Minus class="h-3 w-3" />
                                </Button>
                                <span class="w-8 text-center text-sm font-medium">{{ item.quantity }}</span>
                                <Button variant="outline" size="icon" class="h-7 w-7" @click="updateQuantity(index, 1)">
                                    <Plus class="h-3 w-3" />
                                </Button>
                            </div>
                            <p class="w-20 text-right text-sm font-medium">{{ formatCurrency(item.price * item.quantity) }}</p>
                            <Button variant="ghost" size="icon" class="h-7 w-7 shrink-0" @click="removeFromCart(index)">
                                <Trash2 class="h-3 w-3 text-destructive" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Discount section -->
                <div v-if="operatorCan('pos.discount') && cart.length > 0" class="border-t px-4 py-2">
                    <div class="flex items-center gap-2">
                        <Select v-model="discountType">
                            <SelectTrigger class="w-[100px] h-8 text-xs">
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
                            class="h-8 text-sm"
                        />
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="cart.length > 0" class="border-t px-4 py-2">
                    <Textarea v-model="orderNotes" placeholder="Order notes (optional)" rows="1" class="resize-none text-xs" />
                </div>

                <!-- Totals -->
                <div v-if="cart.length > 0" class="border-t px-4 py-2 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Subtotal</span>
                        <span>{{ formatCurrency(subtotal) }}</span>
                    </div>
                    <div v-if="discountValue > 0" class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>-{{ formatCurrency(discountValue) }}</span>
                    </div>
                    <div v-if="taxAmount > 0" class="flex justify-between text-muted-foreground">
                        <span>{{ taxLabel }}{{ taxInclusive ? ' (incl.)' : '' }}</span>
                        <span>{{ formatCurrency(taxAmount) }}</span>
                    </div>
                    <div class="flex justify-between border-t pt-1 text-lg font-bold">
                        <span>Total</span>
                        <span>{{ formatCurrency(total) }}</span>
                    </div>
                </div>

                <!-- Charge button -->
                <div class="border-t p-4">
                    <Button
                        class="w-full h-12 text-lg font-bold"
                        :disabled="cart.length === 0"
                        @click="openReceiptPreview"
                    >
                        <ShoppingCart class="mr-2 h-5 w-5" />
                        Charge {{ cart.length > 0 ? formatCurrency(total) : '' }}
                    </Button>
                </div>
            </div>
        </div>

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
                    <ReceiptTemplate :data="previewReceiptData" :showPaymentDetails="false" />
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
                    <!-- Payment method tabs -->
                    <div class="grid grid-cols-3 gap-2">
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
                    </div>

                    <!-- Total display -->
                    <div class="rounded-lg bg-muted p-4 text-center">
                        <p class="text-sm text-muted-foreground">Total Amount</p>
                        <p class="text-3xl font-bold">{{ formatCurrency(total) }}</p>
                    </div>

                    <!-- Cash payment -->
                    <div v-if="paymentMethod === 'cash'" class="space-y-3">
                        <div class="space-y-2">
                            <Label>Amount Tendered</Label>
                            <Input v-model.number="amountTendered" type="number" min="0" step="0.01" class="text-lg h-12 text-center font-bold" />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="amount in quickAmounts"
                                :key="amount"
                                variant="outline"
                                size="sm"
                                @click="setQuickAmount(amount)"
                            >
                                {{ formatCurrency(amount) }}
                            </Button>
                        </div>
                        <div v-if="amountTendered >= total" class="rounded-lg bg-green-50 dark:bg-green-950 p-3 text-center">
                            <p class="text-sm text-green-600 dark:text-green-400">Change</p>
                            <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ formatCurrency(changeAmount) }}</p>
                        </div>
                    </div>

                    <!-- Card/E-wallet reference -->
                    <div v-else class="space-y-2">
                        <Label>Reference Number (optional)</Label>
                        <Input v-model="referenceNumber" placeholder="Enter reference number" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showPaymentDialog = false">Cancel</Button>
                    <Button :disabled="!canCheckout || processing" class="min-w-[140px]" @click="processCheckout">
                        {{ processing ? 'Processing...' : 'Complete Sale' }}
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
                        Sale Complete
                    </DialogTitle>
                </DialogHeader>

                <div v-if="completedOrder" id="receipt-print-area" class="max-h-[60vh] overflow-y-auto py-2">
                    <ReceiptTemplate :data="completedReceiptData" :showPaymentDetails="true" />
                </div>

                <DialogFooter class="flex-col gap-2 sm:flex-col">
                    <div class="flex gap-2 w-full">
                        <Button variant="outline" class="flex-1" @click="printReceipt">
                            <Printer class="mr-2 h-4 w-4" />
                            Print
                        </Button>
                        <Button variant="outline" class="flex-1" @click="downloadPdf">
                            <Download class="mr-2 h-4 w-4" />
                            PDF
                        </Button>
                    </div>
                    <Button class="w-full" @click="closeReceipt">New Sale</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </PosLayout>
</template>
