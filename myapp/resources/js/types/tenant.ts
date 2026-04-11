export interface SharedTenantRole {
    id: number;
    name: string;
    slug: string;
    is_system: boolean;
}

export interface TenantSettings {
    store_name?: string;
    store_address?: string;
    store_phone?: string;
    tax_rate?: number;
    tax_label?: string;
    tax_inclusive?: boolean;
    receipt_header?: string;
    receipt_footer?: string;
    currency?: string;
    default_theme?: 'light' | 'dark' | 'system';
    default_language?: string;
    // Receipt customization (Enterprise)
    receipt_logo?: string | null;
    receipt_logo_url?: string | null;
    receipt_show_address?: boolean;
    receipt_show_phone?: boolean;
    receipt_show_customer?: boolean;
    receipt_show_table?: boolean;
    receipt_show_order_type?: boolean;
    receipt_show_tax_breakdown?: boolean;
    receipt_thank_you_message?: string;
    receipt_width?: '58mm' | '80mm';
}

export interface SharedTenant {
    id: string;
    name: string;
    slug: string;
    is_active: boolean;
    settings: TenantSettings;
    subscription: {
        status: string;
        plan: {
            name: string;
            slug: string;
            max_branches: number | null;
            max_users: number | null;
            max_products: number | null;
        } | null;
    } | null;
}

export interface Flash {
    success: string | null;
    error: string | null;
    showWelcome?: boolean;
}
