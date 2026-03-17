<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
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

function togglePlan(slug: string) {
    const index = form.applicable_plans.indexOf(slug);
    if (index === -1) {
        form.applicable_plans.push(slug);
    } else {
        form.applicable_plans.splice(index, 1);
    }
}
</script>

<template>
    <Head title="Create Promo Code" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Create Promo Code</h1>
            </div>

            <div class="mx-auto w-full max-w-2xl rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <form @submit.prevent="submit" class="flex flex-col gap-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="code">Code</Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="e.g. WELCOME20"
                                class="uppercase"
                            />
                            <InputError :message="form.errors.code" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                placeholder="Optional description"
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="discount_type">Discount Type</Label>
                            <select
                                id="discount_type"
                                v-model="form.discount_type"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option v-for="dt in discountTypes" :key="dt.value" :value="dt.value">
                                    {{ dt.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.discount_type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="discount_value">
                                Discount Value {{ form.discount_type === 'percentage' ? '(%)' : '(₱)' }}
                            </Label>
                            <Input
                                id="discount_value"
                                v-model="form.discount_value"
                                type="number"
                                step="0.01"
                                min="0.01"
                                placeholder="e.g. 20"
                            />
                            <InputError :message="form.errors.discount_value" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="max_uses">Max Uses (leave empty for unlimited)</Label>
                        <Input
                            id="max_uses"
                            v-model="form.max_uses"
                            type="number"
                            min="1"
                            placeholder="Unlimited"
                        />
                        <InputError :message="form.errors.max_uses" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="valid_from">Valid From</Label>
                            <Input
                                id="valid_from"
                                v-model="form.valid_from"
                                type="date"
                            />
                            <InputError :message="form.errors.valid_from" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="valid_until">Valid Until</Label>
                            <Input
                                id="valid_until"
                                v-model="form.valid_until"
                                type="date"
                            />
                            <InputError :message="form.errors.valid_until" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label>Applicable Plans (leave unchecked for all plans)</Label>
                        <div class="flex flex-wrap gap-3">
                            <label
                                v-for="plan in plans"
                                :key="plan.slug"
                                class="flex items-center gap-2 rounded-lg border px-3 py-2 cursor-pointer transition"
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

                    <div class="flex items-center gap-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                        />
                        <Label for="is_active" class="cursor-pointer">Active</Label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
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
    </AdminLayout>
</template>
