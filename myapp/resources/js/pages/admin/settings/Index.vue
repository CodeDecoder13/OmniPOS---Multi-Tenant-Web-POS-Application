<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    settings: {
        app_name: string;
        registration_enabled: string;
        default_plan: string;
        trial_days: string;
        support_email: string;
        currency_symbol: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Settings', href: '/admin/settings' },
];

const form = useForm({
    app_name: props.settings.app_name,
    registration_enabled: props.settings.registration_enabled,
    default_plan: props.settings.default_plan,
    trial_days: props.settings.trial_days,
    support_email: props.settings.support_email,
    currency_symbol: props.settings.currency_symbol,
});

function submit() {
    form.put('/admin/settings');
}
</script>

<template>
    <Head title="Settings" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">System Settings</h1>

            <div class="mx-auto w-full max-w-2xl rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <form @submit.prevent="submit" class="flex flex-col gap-5">
                    <div class="grid gap-2">
                        <Label for="app_name">Application Name</Label>
                        <Input id="app_name" v-model="form.app_name" />
                        <InputError :message="form.errors.app_name" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="currency_symbol">Currency Symbol</Label>
                            <Input id="currency_symbol" v-model="form.currency_symbol" />
                            <InputError :message="form.errors.currency_symbol" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="trial_days">Trial Days</Label>
                            <Input id="trial_days" v-model="form.trial_days" type="number" min="0" max="365" />
                            <InputError :message="form.errors.trial_days" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="support_email">Support Email</Label>
                        <Input id="support_email" v-model="form.support_email" type="email" placeholder="support@example.com" />
                        <InputError :message="form.errors.support_email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="default_plan">Default Plan Slug</Label>
                        <Input id="default_plan" v-model="form.default_plan" placeholder="e.g. free" />
                        <InputError :message="form.errors.default_plan" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="registration_enabled">Registration</Label>
                        <select
                            id="registration_enabled"
                            v-model="form.registration_enabled"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                        <InputError :message="form.errors.registration_enabled" />
                    </div>

                    <div class="flex justify-end pt-2">
                        <Button type="submit" :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
                            Save Settings
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
