<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';
import { useTenant } from '@/composables/useTenant';

const { tenantUrl } = useTenant();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Categories', href: tenantUrl('categories') },
    { title: 'Create', href: tenantUrl('categories/create') },
];

const form = useForm({
    name: '',
    slug: '',
    description: '',
    sort_order: 0,
});

function generateSlug() {
    form.slug = form.name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

function submit() {
    form.post(tenantUrl('categories'));
}
</script>

<template>
    <Head title="Create Category" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <h1 class="mb-6 text-2xl font-bold">Create Category</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="space-y-4">
                        <div>
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. Beverages"
                                class="mt-1"
                                @input="generateSlug"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <Label for="slug">Slug</Label>
                            <Input
                                id="slug"
                                v-model="form.slug"
                                placeholder="beverages"
                                class="mt-1"
                            />
                            <p v-if="form.errors.slug" class="mt-1 text-sm text-red-500">{{ form.errors.slug }}</p>
                        </div>

                        <div>
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Optional description..."
                                class="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                rows="3"
                            />
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">{{ form.errors.description }}</p>
                        </div>

                        <div>
                            <Label for="sort_order">Sort Order</Label>
                            <Input
                                id="sort_order"
                                v-model.number="form.sort_order"
                                type="number"
                                min="0"
                                class="mt-1 w-32"
                            />
                            <p v-if="form.errors.sort_order" class="mt-1 text-sm text-red-500">{{ form.errors.sort_order }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" type="button" as-child>
                        <a :href="tenantUrl('categories')">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Category' }}
                    </Button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
