import type { User } from './auth';

export interface Plan {
    id: number;
    name: string;
    slug: string;
    price: string;
    features: string[];
    max_branches: number | null;
    max_users: number | null;
    max_products: number | null;
    is_active: boolean;
    subscriptions_count?: number;
    created_at: string;
    updated_at: string;
}

export interface BusinessTypeOption {
    value: string;
    label: string;
    icon: string;
}

export interface Tenant {
    id: string;
    name: string;
    slug: string;
    business_type: string;
    data: Record<string, unknown> | null;
    owner_id: number;
    is_active: boolean;
    owner?: User;
    subscription?: TenantSubscription;
    users?: User[];
    created_at: string;
    updated_at: string;
}

export interface TenantSubscription {
    id: number;
    tenant_id: string;
    plan_id: number;
    status: string;
    trial_ends_at: string | null;
    ends_at: string | null;
    plan?: Plan;
    created_at: string;
    updated_at: string;
}

export interface PromoCode {
    id: number;
    code: string;
    description: string | null;
    discount_type: 'percentage' | 'fixed';
    discount_value: string;
    max_uses: number | null;
    used_count: number;
    valid_from: string | null;
    valid_until: string | null;
    is_active: boolean;
    applicable_plans: string[] | null;
    subscriptions_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Admin {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    last_login_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface AdminActivityLog {
    id: number;
    admin_id: number;
    action: string;
    subject_type: string | null;
    subject_id: string | null;
    properties: Record<string, unknown> | null;
    ip_address: string | null;
    admin?: { id: number; name: string };
    created_at: string;
}

export interface SystemSetting {
    id: number;
    key: string;
    value: string | null;
    created_at: string;
    updated_at: string;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Permission {
    id: number;
    name: string;
    slug: string;
    group: string;
    description: string | null;
}

export interface Role {
    id: number;
    tenant_id: string;
    name: string;
    slug: string;
    description: string | null;
    is_system: boolean;
    permissions?: Permission[];
    tenant_users_count?: number;
    created_at: string;
    updated_at: string;
}

export interface GroupedPermissions {
    [group: string]: Permission[];
}

export interface BranchSettings {
    pos_enabled: boolean;
    inventory_tracking: boolean;
    customer_loyalty: boolean;
    discounts_enabled: boolean;
    dine_in: boolean;
    takeout: boolean;
    delivery: boolean;
    kitchen_display: boolean;
    receipt_printing: boolean;
}

export interface Branch {
    id: number;
    tenant_id: string;
    name: string;
    code: string;
    address: string | null;
    phone: string | null;
    email: string | null;
    is_active: boolean;
    settings?: BranchSettings;
    created_by: number | null;
    creator?: { id: number; name: string };
    created_at: string;
    updated_at: string;
}

export interface BranchProduct {
    id: number;
    branch_id: number;
    product_id: number;
    custom_price: string | null;
    is_available: boolean;
}

export interface BranchMenuProduct {
    id: number;
    name: string;
    sku: string | null;
    price: string;
    category: { id: number; name: string } | null;
    image_url: string | null;
    effective_price: string;
    custom_price: string | null;
    is_available: boolean;
}

export interface Category {
    id: number;
    tenant_id: string;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    products_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Product {
    id: number;
    tenant_id: string;
    category_id: number | null;
    name: string;
    slug: string;
    sku: string | null;
    description: string | null;
    image_path: string | null;
    image_url: string | null;
    price: string;
    cost_price: string | null;
    is_active: boolean;
    created_by: number | null;
    category?: { id: number; name: string };
    creator?: { id: number; name: string };
    created_at: string;
    updated_at: string;
}

// Inventory Module Types

export type AdjustmentType = 'purchase' | 'sale' | 'return' | 'damage' | 'correction' | 'initial' | 'transfer_out' | 'transfer_in';

export interface Inventory {
    id: number;
    tenant_id: string;
    product_id: number;
    branch_id: number;
    quantity_on_hand: number;
    low_stock_threshold: number;
    product?: Pick<Product, 'id' | 'name' | 'sku' | 'image_url'>;
    branch?: Pick<Branch, 'id' | 'name'>;
    created_at: string;
    updated_at: string;
}

export interface InventoryAdjustment {
    id: number;
    inventory_id: number;
    type: AdjustmentType;
    quantity_before: number;
    quantity_after: number;
    quantity_change: number;
    reason: string | null;
    reference_type: string | null;
    reference_id: number | null;
    creator?: { id: number; name: string };
    created_at: string;
}

// POS Operator (PIN login)

export interface PosOperator {
    user_id: number;
    name: string;
    role: { id: number; name: string; slug: string } | null;
    permissions: string[];
}

// POS Module Types

export type OrderType = 'dine_in' | 'take_out';
export type OrderStatus = 'pending' | 'completed' | 'voided' | 'refunded';
export type KitchenStatus = 'new' | 'preparing' | 'ready' | 'served';
export type PaymentMethod = 'cash' | 'card' | 'e_wallet' | 'bank_transfer' | 'other' | 'online';
export type PaymentStatus = 'pending' | 'completed' | 'failed';
export type DiscountType = 'percentage' | 'fixed';

export interface Customer {
    id: number;
    tenant_id: string;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    notes: string | null;
    is_active: boolean;
    created_by: number | null;
    orders_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Order {
    id: number;
    tenant_id: string;
    branch_id: number | null;
    customer_id: number | null;
    shift_id: number | null;
    table_id: number | null;
    promotion_id: number | null;
    order_number: string;
    subtotal: string;
    discount_amount: string;
    discount_type: DiscountType | null;
    promotion_discount: string;
    tax_amount: string;
    total: string;
    notes: string | null;
    order_type: OrderType;
    status: OrderStatus;
    kitchen_status: KitchenStatus | null;
    kitchen_sent_at: string | null;
    kitchen_completed_at: string | null;
    kitchen_notes: string | null;
    created_by: number | null;
    voided_by: number | null;
    void_reason: string | null;
    voided_at: string | null;
    customer?: Customer | null;
    branch?: { id: number; name: string } | null;
    table?: { id: number; name: string } | null;
    promotion?: { id: number; code: string; name: string } | null;
    creator?: { id: number; name: string } | null;
    voided_by_user?: { id: number; name: string } | null;
    items?: OrderItem[];
    items_count?: number;
    payments?: Payment[];
    created_at: string;
    updated_at: string;
}

export interface OrderItem {
    id: number;
    order_id: number;
    product_id: number | null;
    product_name: string;
    product_price: string;
    quantity: number;
    subtotal: string;
    product?: { id: number; name: string } | null;
    variations?: OrderItemVariation[];
    item_addons?: OrderItemAddon[];
}

export interface Payment {
    id: number;
    order_id: number;
    amount: string;
    method: PaymentMethod;
    reference_number: string | null;
    status: PaymentStatus;
    amount_tendered: string | null;
    change_amount: string | null;
    created_at: string;
    updated_at: string;
}

// Shift Module Types

export type ShiftStatus = 'open' | 'closed';

export interface Shift {
    id: number;
    tenant_id: string;
    branch_id: number | null;
    user_id: number;
    starting_cash: string;
    ending_cash: string | null;
    expected_cash: string | null;
    cash_difference: string | null;
    total_sales: string;
    total_orders: number;
    status: ShiftStatus;
    notes: string | null;
    opened_at: string;
    closed_at: string | null;
    operator?: { id: number; name: string } | null;
    branch?: { id: number; name: string } | null;
    created_at: string;
    updated_at: string;
}

export interface ShiftSummary {
    total_sales: number;
    total_orders: number;
    avg_order_value: number;
    voided_count: number;
    payment_breakdown: { method: string; count: number; total: number }[];
}

export interface ActiveShift {
    shift: Shift | null;
    summary: ShiftSummary | null;
}

// Activity Log Types

export interface ActivityLog {
    id: number;
    tenant_id: string;
    user_id: number;
    action: string;
    subject_type: string | null;
    subject_id: number | null;
    properties: Record<string, unknown> | null;
    actor?: { id: number; name: string } | null;
    created_at: string;
}

// Shift Schedule Types

export type DayOfWeek = 'mon' | 'tue' | 'wed' | 'thu' | 'fri' | 'sat' | 'sun';

export interface ShiftSchedule {
    id: number;
    tenant_id: string;
    user_id: number;
    branch_id: number | null;
    days_of_week: DayOfWeek[];
    start_time: string;
    end_time: string;
    notes: string | null;
    created_by: number;
    operator?: { id: number; name: string } | null;
    branch?: { id: number; name: string } | null;
    creator?: { id: number; name: string } | null;
    created_at: string;
    updated_at: string;
}

// Report Module Types

export interface ReportFilters {
    date_from: string;
    date_to: string;
    period: 'daily' | 'weekly' | 'monthly';
    branch_id: number | null;
}

export interface ReportSummary {
    total_revenue: number;
    order_count: number;
    avg_order_value: number;
    items_sold: number;
    total_discounts: number;
}

export interface SalesTrend {
    labels: string[];
    revenue: number[];
    order_count: number[];
}

export interface TopProductItem {
    product_name: string;
    total_quantity: number;
    total_revenue: number;
}

export interface TopProducts {
    by_quantity: TopProductItem[];
    by_revenue: TopProductItem[];
}

export interface PaymentBreakdownItem {
    method: string;
    label: string;
    count: number;
    total_amount: number;
}

export interface OperatorPerformanceItem {
    user_id: number;
    user_name: string;
    order_count: number;
    total_revenue: number;
    avg_order_value: number;
}

export interface BranchComparisonItem {
    branch_id: number;
    branch_name: string;
    order_count: number;
    total_revenue: number;
    avg_order_value: number;
}

// Table Management Types

export type TableStatus = 'available' | 'occupied' | 'reserved' | 'maintenance';

export interface Table {
    id: number;
    tenant_id: string;
    branch_id: number | null;
    name: string;
    capacity: number;
    status: TableStatus;
    sort_order: number;
    is_active: boolean;
    branch?: Pick<Branch, 'id' | 'name'> | null;
    created_at: string;
    updated_at: string;
}

// Promotion Engine Types

export type PromotionType = 'percentage' | 'fixed' | 'buy_x_get_y';

export interface Promotion {
    id: number;
    tenant_id: string;
    code: string;
    name: string;
    type: PromotionType;
    value: string;
    min_order_amount: string | null;
    max_discount: string | null;
    start_date: string | null;
    end_date: string | null;
    is_active: boolean;
    usage_limit: number | null;
    used_count: number;
    description: string | null;
    created_at: string;
    updated_at: string;
}

// Supplier Module Types

export interface Supplier {
    id: number;
    tenant_id: string;
    name: string;
    contact_person: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    notes: string | null;
    is_active: boolean;
    created_by: number | null;
    products_count?: number;
    created_at: string;
    updated_at: string;
}

export interface ProductSupplier {
    product_id: number;
    supplier_id: number;
    cost_price: string | null;
    supplier_sku: string | null;
    is_preferred: boolean;
}

// Product Variation & Add-on Types

export interface VariationGroup {
    id: number;
    product_id: number;
    name: string;
    sort_order: number;
    is_required: boolean;
    options: VariationOption[];
}

export interface VariationOption {
    id: number;
    variation_group_id: number;
    name: string;
    price_modifier: string;
    sort_order: number;
    is_active: boolean;
}

export interface Addon {
    id: number;
    tenant_id: string;
    name: string;
    price: string;
    category_label: string | null;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

export interface OrderItemVariation {
    id: number;
    order_item_id: number;
    variation_group_name: string;
    option_name: string;
    price_modifier: string;
}

export interface OrderItemAddon {
    id: number;
    order_item_id: number;
    addon_name: string;
    addon_price: string;
}

// Stock Transfer Types

export type TransferStatus = 'pending' | 'in_transit' | 'completed' | 'cancelled';

export interface StockTransfer {
    id: number;
    tenant_id: string;
    transfer_number: string;
    source_branch_id: number;
    destination_branch_id: number;
    status: TransferStatus;
    notes: string | null;
    source_branch?: Pick<Branch, 'id' | 'name'>;
    destination_branch?: Pick<Branch, 'id' | 'name'>;
    items?: StockTransferItem[];
    creator?: { id: number; name: string } | null;
    created_at: string;
    updated_at: string;
}

export interface StockTransferItem {
    id: number;
    stock_transfer_id: number;
    product_id: number;
    quantity_requested: number;
    quantity_sent: number | null;
    quantity_received: number | null;
    product?: Pick<Product, 'id' | 'name' | 'sku'>;
}

// Purchase Order Types

export type PurchaseOrderStatus = 'draft' | 'sent' | 'partial' | 'received' | 'cancelled';

export interface PurchaseOrder {
    id: number;
    tenant_id: string;
    supplier_id: number;
    branch_id: number;
    po_number: string;
    status: PurchaseOrderStatus;
    expected_date: string | null;
    notes: string | null;
    total_amount: string;
    supplier?: Pick<Supplier, 'id' | 'name'>;
    branch?: Pick<Branch, 'id' | 'name'>;
    items?: PurchaseOrderItem[];
    creator?: { id: number; name: string } | null;
    created_at: string;
    updated_at: string;
}

export interface PurchaseOrderItem {
    id: number;
    purchase_order_id: number;
    product_id: number;
    quantity_ordered: number;
    unit_cost: string;
    quantity_received: number;
    subtotal: string;
    product?: Pick<Product, 'id' | 'name' | 'sku'>;
}

// Inventory Report Types

export interface InventoryReportItem {
    product_id: number;
    product_name: string;
    category_name: string;
    branch_name: string;
    quantity_on_hand: number;
    low_stock_threshold: number;
    cost_price: number;
    price: number;
    stock_value: number;
    is_low_stock: boolean;
}

export interface StockMovementItem {
    type: string;
    label: string;
    total_quantity: number;
}

export interface InventoryReport {
    stock_levels: InventoryReportItem[];
    low_stock_items: InventoryReportItem[];
    stock_movement: StockMovementItem[];
    total_stock_value: number;
    branch_valuations: { branch_name: string; total_value: number; item_count: number }[];
}

// Tax Report Types

export interface TaxPeriodItem {
    period: string;
    order_count: number;
    taxable_amount: number;
    tax_amount: number;
}

export interface TaxReport {
    total_tax: number;
    total_taxable: number;
    effective_rate: number;
    tax_inclusive: boolean;
    tax_rate: number;
    by_period: TaxPeriodItem[];
    by_order_type: { type: string; label: string; tax_amount: number; order_count: number }[];
}

// Release Note Types

export type ReleaseNoteItemType = 'feature' | 'fix' | 'improvement';

export interface ReleaseNoteItem {
    type: ReleaseNoteItemType;
    description: string;
}

export interface ReleaseNote {
    id: number;
    title: string;
    version: string;
    summary: string | null;
    items: ReleaseNoteItem[];
    is_published: boolean;
    published_at: string | null;
    created_at: string;
    updated_at: string;
}

// Forecast Types

export interface ForecastData {
    historical: { labels: string[]; revenue: number[] };
    projected: { labels: string[]; revenue: number[] };
    moving_avg_7: (number | null)[];
    moving_avg_30: (number | null)[];
    growth_rate: number;
    projected_revenue_7d: number;
    projected_revenue_14d: number;
    projected_revenue_30d: number;
    day_of_week_pattern: { day: string; avg_revenue: number }[];
}
