<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import InputError from '@/components/InputError.vue';
import {
    Sparkles,
    Rocket,
    Handshake,
    SlidersHorizontal,
    RefreshCw,
    Tag,
    Percent,
    Calendar,
    Users,
    Eye,
    Copy,
    Check,
} from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    plans: { id: number; name: string; slug: string }[];
    discountTypes: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Promo Codes', href: '/admin/promo-codes' },
    { title: 'Create', href: '/admin/promo-codes/create' },
];

const form = useForm({
    code: '',
    description: '',
    discount_type: 'percentage',
    discount_value: '',
    max_uses: undefined as number | undefined,
    valid_from: '',
    valid_until: '',
    is_active: true,
    applicable_plans: [] as string[],
});

const activeTemplate = ref<string | null>(null);
const copied = ref(false);

// --- Template definitions ---
interface Template {
    key: string;
    label: string;
    icon: typeof Sparkles;
    color: string;
    borderColor: string;
    bgColor: string;
    description: string;
}

const templates: Template[] = [
    {
        key: 'beta',
        label: 'Beta Access',
        icon: Sparkles,
        color: 'text-purple-600 dark:text-purple-400',
        borderColor: 'border-purple-300 dark:border-purple-600',
        bgColor: 'bg-purple-50 dark:bg-purple-950/30',
        description: '100% off, all plans',
    },
    {
        key: 'launch',
        label: 'Launch Discount',
        icon: Rocket,
        color: 'text-orange-600 dark:text-orange-400',
        borderColor: 'border-orange-300 dark:border-orange-600',
        bgColor: 'bg-orange-50 dark:bg-orange-950/30',
        description: '50% off, 30-day validity',
    },
    {
        key: 'partner',
        label: 'Partner Code',
        icon: Handshake,
        color: 'text-blue-600 dark:text-blue-400',
        borderColor: 'border-blue-300 dark:border-blue-600',
        bgColor: 'bg-blue-50 dark:bg-blue-950/30',
        description: 'Fixed ₱500 off, unlimited',
    },
    {
        key: 'custom',
        label: 'Custom',
        icon: SlidersHorizontal,
        color: 'text-gray-600 dark:text-gray-400',
        borderColor: 'border-gray-300 dark:border-gray-600',
        bgColor: 'bg-gray-50 dark:bg-gray-950/30',
        description: 'Start from scratch',
    },
];

function randomSuffix(): string {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let result = '';
    for (let i = 0; i < 4; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

function getProEnterpriseSlugs(): string[] {
    return props.plans
        .filter((p) => /pro|enterprise/i.test(p.slug) || /pro|enterprise/i.test(p.name))
        .map((p) => p.slug);
}

function applyTemplate(key: string) {
    activeTemplate.value = key;

    if (key === 'custom') {
        form.code = '';
        form.description = '';
        form.discount_type = 'percentage';
        form.discount_value = '';
        form.max_uses = undefined;
        form.valid_from = '';
        form.valid_until = '';
        form.is_active = true;
        form.applicable_plans = [];
        return;
    }

    if (key === 'beta') {
        form.code = `BETA-${randomSuffix()}`;
        form.description = 'Beta access — full discount';
        form.discount_type = 'percentage';
        form.discount_value = '100';
        form.max_uses = undefined;
        form.valid_from = '';
        form.valid_until = '';
        form.is_active = true;
        form.applicable_plans = [];
    } else if (key === 'launch') {
        const now = new Date();
        const until = new Date(now);
        until.setDate(until.getDate() + 30);
        form.code = `LAUNCH-${randomSuffix()}`;
        form.description = 'Launch discount — 50% off';
        form.discount_type = 'percentage';
        form.discount_value = '50';
        form.max_uses = undefined;
        form.valid_from = now.toISOString().slice(0, 10);
        form.valid_until = until.toISOString().slice(0, 10);
        form.is_active = true;
        form.applicable_plans = getProEnterpriseSlugs();
    } else if (key === 'partner') {
        form.code = `PARTNER-${randomSuffix()}`;
        form.description = 'Partner code — ₱500 off';
        form.discount_type = 'fixed';
        form.discount_value = '500';
        form.max_uses = undefined;
        form.valid_from = '';
        form.valid_until = '';
        form.is_active = true;
        form.applicable_plans = getProEnterpriseSlugs();
    }
}

function generateCode() {
    const prefixMap: Record<string, string> = {
        beta: 'BETA',
        launch: 'LAUNCH',
        partner: 'PARTNER',
    };
    const prefix = (activeTemplate.value && prefixMap[activeTemplate.value]) || 'PROMO';
    form.code = `${prefix}-${randomSuffix()}`;
}

async function copyCode() {
    if (!form.code) return;
    try {
        await navigator.clipboard.writeText(form.code.toUpperCase());
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        // Clipboard API not available
    }
}

function togglePlan(slug: string) {
    const index = form.applicable_plans.indexOf(slug);
    if (index === -1) {
        form.applicable_plans.push(slug);
    } else {
        form.applicable_plans.splice(index, 1);
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        code: data.code.toUpperCase(),
        applicable_plans: data.applicable_plans.length > 0 ? data.applicable_plans : null,
        max_uses: data.max_uses || null,
        valid_from: data.valid_from || null,
        valid_until: data.valid_until || null,
    })).post('/admin/promo-codes');
}

// --- Live preview computed ---
const previewDiscount = computed(() => {
    if (!form.discount_value) return '—';
    return form.discount_type === 'percentage'
        ? `${form.discount_value}%`
        : `₱${Number(form.discount_value).toLocaleString()}`;
});

const previewPlans = computed(() => {
    if (form.applicable_plans.length === 0) return 'All plans';
    return props.plans
        .filter((p) => form.applicable_plans.includes(p.slug))
        .map((p) => p.name)
        .join(', ');
});

const previewValidity = computed(() => {
    if (!form.valid_from && !form.valid_until) return 'No limit';
    const from = form.valid_from || '...';
    const until = form.valid_until || '...';
    return `${from} → ${until}`;
});
</script>

<template>
    <Head title="Create Promo Code" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Create Promo Code</h1>
            </div>

            <!-- Two-column layout -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left: Template picker + Form (2/3) -->
                <div class="flex flex-col gap-6 lg:col-span-2">
                    <!-- Template Picker -->
                    <div>
                        <p class="mb-3 text-sm font-medium text-muted-foreground">Quick Start — pick a template or start custom</p>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <button
                                v-for="t in templates"
                                :key="t.key"
                                type="button"
                                class="flex flex-col items-center gap-2 rounded-xl border-2 p-4 text-center transition-all hover:shadow-md"
                                :class="activeTemplate === t.key
                                    ? `${t.borderColor} ${t.bgColor} shadow-sm`
                                    : 'border-transparent bg-white dark:bg-gray-900 hover:border-gray-200 dark:hover:border-gray-700'"
                                @click="applyTemplate(t.key)"
                            >
                                <component :is="t.icon" class="size-6" :class="t.color" />
                                <span class="text-sm font-semibold">{{ t.label }}</span>
                                <span class="text-xs text-muted-foreground">{{ t.description }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Form Card -->
                    <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                        <form @submit.prevent="submit" class="flex flex-col gap-6">

                            <!-- Section: Code & Description -->
                            <div>
                                <div class="mb-4 flex items-center gap-2">
                                    <Tag class="size-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Code & Description</h2>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="code">Promo Code</Label>
                                        <div class="flex gap-1.5">
                                            <Input
                                                id="code"
                                                v-model="form.code"
                                                placeholder="e.g. WELCOME20"
                                                class="flex-1 uppercase"
                                            />
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Button type="button" variant="outline" size="icon" @click="generateCode">
                                                            <RefreshCw class="size-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>Generate random code</TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Button type="button" variant="outline" size="icon" @click="copyCode" :disabled="!form.code">
                                                            <Check v-if="copied" class="size-4 text-green-600" />
                                                            <Copy v-else class="size-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>{{ copied ? 'Copied!' : 'Copy code' }}</TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                        <p class="text-xs text-muted-foreground">Unique code customers will enter at checkout.</p>
                                        <InputError :message="form.errors.code" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="description">Description</Label>
                                        <Input
                                            id="description"
                                            v-model="form.description"
                                            placeholder="Optional internal note"
                                        />
                                        <p class="text-xs text-muted-foreground">Internal note — not shown to customers.</p>
                                        <InputError :message="form.errors.description" />
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <!-- Section: Discount -->
                            <div>
                                <div class="mb-4 flex items-center gap-2">
                                    <Percent class="size-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Discount</h2>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="discount_type">Discount Type</Label>
                                        <Select
                                            :model-value="form.discount_type"
                                            @update:model-value="(val) => form.discount_type = String(val)"
                                        >
                                            <SelectTrigger id="discount_type">
                                                <SelectValue placeholder="Select type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="dt in discountTypes"
                                                    :key="dt.value"
                                                    :value="dt.value"
                                                >
                                                    {{ dt.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-muted-foreground">Percentage takes a % off; fixed subtracts a flat amount.</p>
                                        <InputError :message="form.errors.discount_type" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="discount_value">
                                            Value {{ form.discount_type === 'percentage' ? '(%)' : '(₱)' }}
                                        </Label>
                                        <Input
                                            id="discount_value"
                                            v-model="form.discount_value"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            placeholder="e.g. 20"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            {{ form.discount_type === 'percentage' ? 'Enter 100 for fully free.' : 'Amount in Philippine Peso.' }}
                                        </p>
                                        <InputError :message="form.errors.discount_value" />
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <!-- Section: Limits & Validity -->
                            <div>
                                <div class="mb-4 flex items-center gap-2">
                                    <Calendar class="size-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Limits & Validity</h2>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div class="grid gap-2">
                                        <Label for="max_uses">Max Uses</Label>
                                        <Input
                                            id="max_uses"
                                            v-model="form.max_uses"
                                            type="number"
                                            min="1"
                                            placeholder="Unlimited"
                                        />
                                        <p class="text-xs text-muted-foreground">Leave empty for unlimited redemptions.</p>
                                        <InputError :message="form.errors.max_uses" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="valid_from">Valid From</Label>
                                        <Input
                                            id="valid_from"
                                            v-model="form.valid_from"
                                            type="date"
                                        />
                                        <p class="text-xs text-muted-foreground">When this code becomes usable.</p>
                                        <InputError :message="form.errors.valid_from" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="valid_until">Valid Until</Label>
                                        <Input
                                            id="valid_until"
                                            v-model="form.valid_until"
                                            type="date"
                                        />
                                        <p class="text-xs text-muted-foreground">Code expires after this date.</p>
                                        <InputError :message="form.errors.valid_until" />
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <!-- Section: Plan Targeting -->
                            <div>
                                <div class="mb-4 flex items-center gap-2">
                                    <Users class="size-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Plan Targeting</h2>
                                </div>
                                <p class="mb-3 text-xs text-muted-foreground">
                                    Select which plans this code works with. Leave all unchecked to allow any plan.
                                </p>
                                <div class="flex flex-wrap gap-3">
                                    <label
                                        v-for="plan in plans"
                                        :key="plan.slug"
                                        class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 transition"
                                        :class="form.applicable_plans.includes(plan.slug)
                                            ? 'border-teal-600 bg-teal-50 dark:border-teal-500 dark:bg-teal-950/20'
                                            : 'border-gray-200 dark:border-gray-700'"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="form.applicable_plans.includes(plan.slug)"
                                            class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                            @change="togglePlan(plan.slug)"
                                        />
                                        <span class="text-sm">{{ plan.name }}</span>
                                    </label>
                                </div>
                                <InputError :message="form.errors.applicable_plans" />
                            </div>

                            <Separator />

                            <!-- Section: Status -->
                            <div>
                                <div class="mb-4 flex items-center gap-2">
                                    <Eye class="size-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Status</h2>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Switch
                                        id="is_active"
                                        :checked="form.is_active"
                                        @update:checked="(val: boolean) => form.is_active = val"
                                    />
                                    <Label for="is_active" class="cursor-pointer">
                                        {{ form.is_active ? 'Active' : 'Inactive' }}
                                    </Label>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Inactive codes cannot be redeemed. You can activate them later.
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end gap-2 border-t pt-4 dark:border-gray-800">
                                <Button type="button" variant="outline" as-child>
                                    <Link href="/admin/promo-codes">Cancel</Link>
                                </Button>
                                <Button type="submit" :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
                                    Create Promo Code
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Live Preview (1/3) -->
                <div class="lg:col-span-1">
                    <div class="sticky top-6 rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-4 flex items-center gap-2">
                            <Eye class="size-4 text-muted-foreground" />
                            <h2 class="text-sm font-semibold">Live Preview</h2>
                        </div>

                        <div class="flex flex-col gap-4">
                            <!-- Code badge -->
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Code</span>
                                <div>
                                    <Badge v-if="form.code" variant="secondary" class="font-mono text-sm tracking-wider">
                                        {{ form.code.toUpperCase() }}
                                    </Badge>
                                    <span v-else class="text-sm text-muted-foreground italic">No code yet</span>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Description</span>
                                <span class="text-sm">{{ form.description || '—' }}</span>
                            </div>

                            <Separator />

                            <!-- Discount -->
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Discount</span>
                                <span class="text-lg font-bold text-teal-600 dark:text-teal-400">
                                    {{ previewDiscount }}
                                </span>
                            </div>

                            <!-- Max uses -->
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Max Uses</span>
                                <span class="text-sm">{{ form.max_uses || 'Unlimited' }}</span>
                            </div>

                            <!-- Validity -->
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Validity</span>
                                <span class="text-sm">{{ previewValidity }}</span>
                            </div>

                            <Separator />

                            <!-- Plans -->
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Applicable Plans</span>
                                <span class="text-sm">{{ previewPlans }}</span>
                            </div>

                            <!-- Status -->
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Status</span>
                                <div>
                                    <Badge :variant="form.is_active ? 'default' : 'secondary'">
                                        {{ form.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
