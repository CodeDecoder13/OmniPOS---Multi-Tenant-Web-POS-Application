<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { BreadcrumbItem, Category } from '@/types';
import type { AcceptableValue } from 'reka-ui';
import { useTenant } from '@/composables/useTenant';

const props = defineProps<{
    categories: Pick<Category, 'id' | 'name'>[];
}>();

const { tenantUrl } = useTenant();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Products', href: tenantUrl('products') },
    { title: 'Create', href: tenantUrl('products/create') },
];

const form = useForm({
    category_id: null as number | null,
    name: '',
    slug: '',
    sku: '',
    description: '',
    image: null as File | null,
    price: 0,
    cost_price: undefined as number | undefined,
});

const categoryModel = ref('none');
const localCategories = ref([...props.categories]);

// Image upload
const imagePreview = ref<string | null>(null);
const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function onCategoryChange(value: AcceptableValue) {
    const val = String(value);
    categoryModel.value = val;
    form.category_id = val === 'none' ? null : Number(val);
}

function generateSlug() {
    form.slug = form.name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

function handleFileSelect(file: File) {
    if (!file.type.startsWith('image/')) return;
    if (file.size > 2 * 1024 * 1024) return; // 2MB limit
    form.image = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
}

function onFileInput(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files?.[0]) {
        handleFileSelect(target.files[0]);
    }
}

function onDrop(event: DragEvent) {
    isDragging.value = false;
    if (event.dataTransfer?.files?.[0]) {
        handleFileSelect(event.dataTransfer.files[0]);
    }
}

function removeImage() {
    form.image = null;
    imagePreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
}

function triggerFileInput() {
    fileInput.value?.click();
}

// Inline category creation
const showCategoryDialog = ref(false);
const newCategoryName = ref('');
const newCategorySlug = ref('');
const categoryCreating = ref(false);
const categoryError = ref('');

function generateCategorySlug() {
    newCategorySlug.value = newCategoryName.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

async function createCategory() {
    if (!newCategoryName.value.trim()) return;
    categoryCreating.value = true;
    categoryError.value = '';

    try {
        const response = await fetch(tenantUrl('categories/inline'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                ),
            },
            body: JSON.stringify({
                name: newCategoryName.value,
                slug: newCategorySlug.value,
            }),
        });

        if (!response.ok) {
            const data = await response.json();
            categoryError.value = data.errors?.name?.[0] || data.errors?.slug?.[0] || 'Failed to create category.';
            return;
        }

        const category = await response.json();
        localCategories.value.push(category);
        localCategories.value.sort((a, b) => a.name.localeCompare(b.name));
        categoryModel.value = String(category.id);
        form.category_id = category.id;
        showCategoryDialog.value = false;
        newCategoryName.value = '';
        newCategorySlug.value = '';
    } catch {
        categoryError.value = 'Failed to create category.';
    } finally {
        categoryCreating.value = false;
    }
}

function submit() {
    form.post(tenantUrl('products'), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Create Product" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-5xl p-6">
            <h1 class="mb-6 text-2xl font-bold">Create Product</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-5">
                    <!-- Left Column: Product Image -->
                    <div class="lg:col-span-2">
                        <div class="rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <h2 class="mb-4 text-sm font-medium">Product Image</h2>

                            <!-- Image Preview -->
                            <div v-if="imagePreview" class="relative">
                                <img
                                    :src="imagePreview"
                                    alt="Product preview"
                                    class="h-64 w-full rounded-lg object-cover"
                                />
                                <button
                                    type="button"
                                    @click="removeImage"
                                    class="absolute top-2 right-2 rounded-full bg-black/60 p-1.5 text-white transition hover:bg-black/80"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Drop Zone -->
                            <div
                                v-else
                                @click="triggerFileInput"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="onDrop"
                                :class="[
                                    'flex h-64 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed transition',
                                    isDragging
                                        ? 'border-primary bg-primary/5'
                                        : 'border-gray-300 hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500',
                                ]"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="mb-3 h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Click to upload or drag & drop
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    JPG, PNG or WebP (max 2MB)
                                </p>
                            </div>

                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                                @change="onFileInput"
                            />
                            <p v-if="form.errors.image" class="mt-2 text-sm text-red-500">{{ form.errors.image }}</p>
                        </div>
                    </div>

                    <!-- Right Column: Product Details -->
                    <div class="lg:col-span-3">
                        <div class="rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <h2 class="mb-4 text-sm font-medium">Product Details</h2>

                            <div class="space-y-4">
                                <div>
                                    <Label for="name">Product Name</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="e.g. Iced Coffee"
                                        class="mt-1"
                                        @input="generateSlug"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label for="slug">Slug</Label>
                                        <Input
                                            id="slug"
                                            v-model="form.slug"
                                            placeholder="iced-coffee"
                                            class="mt-1"
                                        />
                                        <p v-if="form.errors.slug" class="mt-1 text-sm text-red-500">{{ form.errors.slug }}</p>
                                    </div>

                                    <div>
                                        <Label for="sku">SKU</Label>
                                        <Input
                                            id="sku"
                                            v-model="form.sku"
                                            placeholder="BEV-001"
                                            class="mt-1"
                                        />
                                        <p v-if="form.errors.sku" class="mt-1 text-sm text-red-500">{{ form.errors.sku }}</p>
                                    </div>
                                </div>

                                <div>
                                    <Label for="category_id">Category</Label>
                                    <div class="mt-1 flex gap-2">
                                        <div class="flex-1">
                                            <Select :model-value="categoryModel" @update:model-value="onCategoryChange">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select a category" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">No Category</SelectItem>
                                                    <SelectItem v-for="cat in localCategories" :key="cat.id" :value="String(cat.id)">
                                                        {{ cat.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            @click="showCategoryDialog = true"
                                            title="Add new category"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                            </svg>
                                        </Button>
                                    </div>
                                    <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-500">{{ form.errors.category_id }}</p>
                                </div>

                                <div>
                                    <Label for="description">Description</Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Optional description..."
                                        class="mt-1 min-h-[80px]"
                                        rows="3"
                                    />
                                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">{{ form.errors.description }}</p>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label for="price">Price</Label>
                                        <Input
                                            id="price"
                                            v-model.number="form.price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00"
                                            class="mt-1"
                                        />
                                        <p v-if="form.errors.price" class="mt-1 text-sm text-red-500">{{ form.errors.price }}</p>
                                    </div>

                                    <div>
                                        <Label for="cost_price">Cost Price</Label>
                                        <Input
                                            id="cost_price"
                                            v-model.number="form.cost_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00"
                                            class="mt-1"
                                        />
                                        <p v-if="form.errors.cost_price" class="mt-1 text-sm text-red-500">{{ form.errors.cost_price }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" type="button" as-child>
                        <a :href="tenantUrl('products')">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Product' }}
                    </Button>
                </div>
            </form>
        </div>

        <!-- Inline Category Dialog -->
        <Dialog v-model:open="showCategoryDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>New Category</DialogTitle>
                    <DialogDescription>Create a new category for your products.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div>
                        <Label for="new-category-name">Name</Label>
                        <Input
                            id="new-category-name"
                            v-model="newCategoryName"
                            placeholder="e.g. Beverages"
                            class="mt-1"
                            @input="generateCategorySlug"
                        />
                    </div>
                    <div>
                        <Label for="new-category-slug">Slug</Label>
                        <Input
                            id="new-category-slug"
                            v-model="newCategorySlug"
                            placeholder="beverages"
                            class="mt-1"
                        />
                    </div>
                    <p v-if="categoryError" class="text-sm text-red-500">{{ categoryError }}</p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="showCategoryDialog = false">Cancel</Button>
                    <Button type="button" :disabled="categoryCreating || !newCategoryName.trim()" @click="createCategory">
                        {{ categoryCreating ? 'Creating...' : 'Create' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
