<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { ref } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { Branch, BranchSettings, BreadcrumbItem } from '@/types';
import { useTenant } from '@/composables/useTenant';

const props = defineProps<{
    branch: Branch;
    settings: BranchSettings;
}>();

const { tenantUrl } = useTenant();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Branches', href: tenantUrl('branches') },
    { title: props.branch.name, href: tenantUrl(`branches/${props.branch.id}/edit`) },
    { title: 'Feature Settings', href: tenantUrl(`branches/${props.branch.id}/settings`) },
];

const saving = ref(false);

const form = ref<BranchSettings>({ ...props.settings });

interface ToggleItem {
    key: keyof BranchSettings;
    label: string;
    description: string;
}

const groups: { title: string; items: ToggleItem[] }[] = [
    {
        title: 'POS & Orders',
        items: [
            { key: 'pos_enabled', label: 'POS Terminal', description: 'Enable POS terminal access for this branch' },
            { key: 'discounts_enabled', label: 'Discounts', description: 'Allow applying discounts at the POS' },
            { key: 'dine_in', label: 'Dine-in', description: 'Enable dine-in order type' },
            { key: 'takeout', label: 'Takeout', description: 'Enable takeout order type' },
            { key: 'delivery', label: 'Delivery', description: 'Enable delivery order type' },
        ],
    },
    {
        title: 'Operations',
        items: [
            { key: 'inventory_tracking', label: 'Inventory Tracking', description: 'Track stock levels for this branch' },
            { key: 'kitchen_display', label: 'Kitchen Display', description: 'Enable kitchen display system' },
            { key: 'receipt_printing', label: 'Receipt Printing', description: 'Auto-print receipts after checkout' },
        ],
    },
    {
        title: 'Customer',
        items: [
            { key: 'customer_loyalty', label: 'Customer Loyalty', description: 'Enable customer loyalty program' },
        ],
    },
];

function saveSettings() {
    saving.value = true;

    router.put(
        tenantUrl(`branches/${props.branch.id}/settings`),
        { ...form.value },
        {
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="`${branch.name} — Feature Settings`" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Feature Settings</h1>
                    <p class="text-sm text-muted-foreground">
                        Toggle features for <strong>{{ branch.name }}</strong>
                    </p>
                </div>
            </div>

            <div class="space-y-6">
                <div
                    v-for="group in groups"
                    :key="group.title"
                    class="rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <h2 class="mb-4 text-lg font-semibold">{{ group.title }}</h2>
                    <div class="space-y-4">
                        <div
                            v-for="item in group.items"
                            :key="item.key"
                            class="flex items-center justify-between"
                        >
                            <div>
                                <Label class="text-sm font-medium">{{ item.label }}</Label>
                                <p class="text-xs text-muted-foreground">{{ item.description }}</p>
                            </div>
                            <Switch
                                :checked="form[item.key]"
                                @update:checked="form[item.key] = $event"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button @click="saveSettings" :disabled="saving">
                        <Save class="mr-2 h-4 w-4" />
                        {{ saving ? 'Saving...' : 'Save Settings' }}
                    </Button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
