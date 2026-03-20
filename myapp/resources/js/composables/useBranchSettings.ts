import { usePage } from '@inertiajs/vue3';

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

export function useBranchSettings() {
    const page = usePage();

    const settings = page.props.branchSettings as BranchSettings | null;

    function isEnabled(feature: keyof BranchSettings): boolean {
        if (!settings) return true;
        return settings[feature] ?? true;
    }

    return { settings, isEnabled };
}
