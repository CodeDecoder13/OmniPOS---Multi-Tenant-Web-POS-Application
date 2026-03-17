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

export interface Branch {
    id: number;
    tenant_id: string;
    name: string;
    code: string;
    address: string | null;
    phone: string | null;
    email: string | null;
    is_active: boolean;
    created_by: number | null;
    creator?: { id: number; name: string };
    created_at: string;
    updated_at: string;
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

export type AdjustmentType = 'purchase' | 'sale' | 'return' | 'damage' | 'correction' | 'initial';

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
export type PaymentMethod = 'cash' | 'card' | 'e_wallet' | 'bank_transfer' | 'other';
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
    order_number: string;
    subtotal: string;
    discount_amount: string;
    discount_type: DiscountType | null;
    tax_amount: string;
    total: string;
    notes: string | null;
    order_type: OrderType;
    status: OrderStatus;
    created_by: number | null;
    customer?: Customer | null;
    branch?: { id: number; name: string } | null;
    creator?: { id: number; name: string } | null;
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
