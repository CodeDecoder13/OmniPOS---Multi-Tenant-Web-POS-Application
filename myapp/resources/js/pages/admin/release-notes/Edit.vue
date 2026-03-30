<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import InputError from '@/components/InputError.vue';
import { Plus, Trash2 } from 'lucide-vue-next';
import type { BreadcrumbItem, ReleaseNote } from '@/types';

const props = defineProps<{
    releaseNote: ReleaseNote;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Release Notes', href: '/admin/release-notes' },
    { title: 'Edit', href: `/admin/release-notes/${props.releaseNote.id}/edit` },
];

const form = useForm({
    title: props.releaseNote.title,
    version: props.releaseNote.version,
    summary: props.releaseNote.summary ?? '',
    items: props.releaseNote.items.map((item) => ({ type: item.type as string, description: item.description })),
    is_published: props.releaseNote.is_published,
    published_at: props.releaseNote.published_at ? props.releaseNote.published_at.substring(0, 10) : '',
});

function addItem() {
    form.items.push({ type: 'feature', description: '' });
}

function removeItem(index: number) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        summary: data.summary || null,
        published_at: data.published_at || null,
    })).put(`/admin/release-notes/${props.releaseNote.id}`);
}
</script>

<template>
    <Head title="Edit Release Note" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Edit Release Note</h1>
            </div>

            <div class="mx-auto w-full max-w-2xl rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <form @submit.prevent="submit" class="flex flex-col gap-6">
                    <!-- Title & Version -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="title">Title</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                placeholder="e.g. March 2026 Update"
                            />
                            <InputError :message="form.errors.title" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="version">Version</Label>
                            <Input
                                id="version"
                                v-model="form.version"
                                placeholder="e.g. 1.5.0"
                            />
                            <InputError :message="form.errors.version" />
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="grid gap-2">
                        <Label for="summary">Summary (optional)</Label>
                        <textarea
                            id="summary"
                            v-model="form.summary"
                            rows="2"
                            placeholder="Brief overview of this release..."
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        ></textarea>
                        <InputError :message="form.errors.summary" />
                    </div>

                    <Separator />

                    <!-- Items -->
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <Label>Items</Label>
                            <Button type="button" variant="outline" size="sm" @click="addItem">
                                <Plus class="mr-1 size-3" />
                                Add Item
                            </Button>
                        </div>
                        <InputError :message="form.errors.items" class="mb-2" />

                        <div class="space-y-3">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="flex items-start gap-2"
                            >
                                <div class="w-36 shrink-0">
                                    <Select
                                        :model-value="item.type"
                                        @update:model-value="(val) => item.type = String(val)"
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="feature">Feature</SelectItem>
                                            <SelectItem value="fix">Bug Fix</SelectItem>
                                            <SelectItem value="improvement">Improvement</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="(form.errors as any)[`items.${index}.type`]" />
                                </div>
                                <div class="flex-1">
                                    <Input
                                        v-model="item.description"
                                        placeholder="Describe the change..."
                                    />
                                    <InputError :message="(form.errors as any)[`items.${index}.description`]" />
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="shrink-0 text-red-500 hover:text-red-700"
                                    :disabled="form.items.length <= 1"
                                    @click="removeItem(index)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <Separator />

                    <!-- Publishing -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex items-center gap-3">
                            <Switch
                                id="is_published"
                                :checked="form.is_published"
                                @update:checked="(val: boolean) => form.is_published = val"
                            />
                            <Label for="is_published" class="cursor-pointer">
                                {{ form.is_published ? 'Published' : 'Draft' }}
                            </Label>
                        </div>
                        <div class="grid gap-2">
                            <Label for="published_at">Published At</Label>
                            <Input
                                id="published_at"
                                v-model="form.published_at"
                                type="date"
                            />
                            <p class="text-xs text-muted-foreground">Auto-set to now when publishing if left empty.</p>
                            <InputError :message="form.errors.published_at" />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-2 border-t pt-4 dark:border-gray-800">
                        <Button type="button" variant="outline" as-child>
                            <Link href="/admin/release-notes">Cancel</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
                            Update Release Note
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
