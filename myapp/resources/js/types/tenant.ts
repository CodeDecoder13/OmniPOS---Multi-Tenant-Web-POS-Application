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
