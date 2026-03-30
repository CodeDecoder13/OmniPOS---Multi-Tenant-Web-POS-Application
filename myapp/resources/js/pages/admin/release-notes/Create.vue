<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import InputError from '@/components/InputError.vue';
import {
    Plus,
    Trash2,
    Rocket,
    Sparkles,
    Bug,
    SlidersHorizontal,
    Eye,
    FileText,
    ListChecks,
} from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import type { ReleaseNoteItemType } from '@/types/models';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Release Notes', href: '/admin/release-notes' },
    { title: 'Create', href: '/admin/release-notes/create' },
];

const form = useForm({
    title: '',
    version: '',
    summary: '',
    items: [{ type: 'feature', description: '' }] as { type: string; description: string }[],
    is_published: false,
    published_at: '',
});

// --- Template definitions ---
const activeTemplate = ref<string | null>(null);

interface Template {
    key: string;
    label: string;
    icon: typeof Rocket;
    color: string;
    borderColor: string;
    bgColor: string;
    description: string;
}

const templates: Template[] = [
    {
        key: 'major',
        label: 'Major Release',
        icon: Rocket,
        color: 'text-orange-600 dark:text-orange-400',
        borderColor: 'border-orange-300 dark:border-orange-600',
        bgColor: 'bg-orange-50 dark:bg-orange-950/30',
        description: 'Full release with mixed items',
    },
    {
        key: 'feature',
        label: 'Feature Drop',
        icon: Sparkles,
        color: 'text-purple-600 dark:text-purple-400',
        borderColor: 'border-purple-300 dark:border-purple-600',
        bgColor: 'bg-purple-50 dark:bg-purple-950/30',
        description: 'New features announcement',
    },
    {
        key: 'hotfix',
        label: 'Hotfix',
        icon: Bug,
        color: 'text-red-600 dark:text-red-400',
        borderColor: 'border-red-300 dark:border-red-600',
        bgColor: 'bg-red-50 dark:bg-red-950/30',
        description: 'Quick bug fix patch',
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

const now = new Date();
const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

function applyTemplate(key: string) {
    activeTemplate.value = key;

    if (key === 'custom') {
        form.title = '';
        form.version = '';
        form.summary = '';
        form.items = [{ type: 'feature', description: '' }];
        form.is_published = false;
        form.published_at = '';
        return;
    }

    if (key === 'major') {
        form.title = 'v1.0 Release';
        form.version = '1.0.0';
        form.summary = 'A major release with new features, improvements, and bug fixes.';
        form.items = [
            { type: 'feature', description: 'New dashboard with real-time analytics' },
            { type: 'improvement', description: 'Improved page load performance' },
            { type: 'fix', description: 'Fixed session timeout issue' },
        ];
        form.is_published = true;
    } else if (key === 'feature') {
        form.title = `New Features — ${monthNames[now.getMonth()]} ${now.getFullYear()}`;
        form.version = '';
        form.summary = 'Exciting new features to boost your workflow.';
        form.items = [
            { type: 'feature', description: '' },
            { type: 'feature', description: '' },
            { type: 'feature', description: '' },
        ];
        form.is_published = true;
    } else if (key === 'hotfix') {
        form.title = 'Hotfix v1.0.1';
        form.version = '1.0.1';
        form.summary = '';
        form.items = [
            { type: 'fix', description: '' },
            { type: 'fix', description: '' },
        ];
        form.is_published = true;
    }
}

// --- Type badge map (same colors as WelcomeBackModal) ---
const typeBadge: Record<ReleaseNoteItemType, { label: string; class: string }> = {
    feature: { label: 'NEW', class: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400' },
    fix: { label: 'FIX', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    improvement: { label: 'IMP', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
};

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
    })).post('/admin/release-notes');
}
</script>

<template>
    <Head title="Create Release Note" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Create Release Note</h1>
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
                            <!-- Section: Title & Version -->
                            <div>
                                <div class="mb-4 flex items-center gap-2">
                                    <FileText class="size-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Title & Version</h2>
                                </div>
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
                            </div>

                            <Separator />

                            <!-- Section: Summary -->
                            <div>
                                <div class="mb-4 flex items-center gap-2">
                                    <FileText class="size-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Summary</h2>
                                </div>
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
                            </div>

                            <Separator />

                            <!-- Section: Items -->
                            <div>
                                <div class="mb-4 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <ListChecks class="size-4 text-muted-foreground" />
                                        <h2 class="text-sm font-semibold">Items</h2>
                                    </div>
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

                            <!-- Section: Publishing -->
                            <div>
                                <div class="mb-4 flex items-center gap-2">
                                    <Eye class="size-4 text-muted-foreground" />
                                    <h2 class="text-sm font-semibold">Publishing</h2>
                                </div>
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
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end gap-2 border-t pt-4 dark:border-gray-800">
                                <Button type="button" variant="outline" as-child>
                                    <Link href="/admin/release-notes">Cancel</Link>
                                </Button>
                                <Button type="submit" :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
                                    Create Release Note
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
                            <!-- Title + Version -->
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Title</span>
                                <div class="flex items-center gap-2">
                                    <span v-if="form.title" class="text-sm font-semibold">{{ form.title }}</span>
                                    <span v-else class="text-sm italic text-muted-foreground">No title yet</span>
                                    <Badge v-if="form.version" variant="secondary" class="text-xs">
                                        v{{ form.version }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Summary -->
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Summary</span>
                                <span class="text-sm">{{ form.summary || '—' }}</span>
                            </div>

                            <Separator />

                            <!-- Items Preview -->
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Items</span>
                                <div v-if="form.items.some(i => i.description)" class="mt-1 space-y-1.5">
                                    <div
                                        v-for="(item, index) in form.items"
                                        :key="index"
                                        class="flex items-start gap-2 text-xs"
                                    >
                                        <span
                                            class="mt-0.5 shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold"
                                            :class="typeBadge[item.type as ReleaseNoteItemType]?.class"
                                        >
                                            {{ typeBadge[item.type as ReleaseNoteItemType]?.label }}
                                        </span>
                                        <span class="text-muted-foreground">
                                            {{ item.description || 'Empty description...' }}
                                        </span>
                                    </div>
                                </div>
                                <span v-else class="text-sm italic text-muted-foreground">No items yet</span>
                            </div>

                            <Separator />

                            <!-- Published Status -->
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">Status</span>
                                <div>
                                    <Badge :class="form.is_published
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                    >
                                        {{ form.is_published ? 'Published' : 'Draft' }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Modal-style preview card -->
                            <Separator />
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium text-muted-foreground">As seen in "What's New"</span>
                                <div class="mt-1 rounded-lg border bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-800/30">
                                    <div class="mb-2 flex items-center gap-2">
                                        <span class="text-sm font-semibold">{{ form.title || 'Untitled' }}</span>
                                        <span v-if="form.version" class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium dark:bg-gray-700">
                                            v{{ form.version }}
                                        </span>
                                    </div>
                                    <ul v-if="form.items.length" class="space-y-1">
                                        <li v-for="(item, i) in form.items" :key="i" class="flex items-start gap-2 text-xs">
                                            <span
                                                class="mt-0.5 shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold"
                                                :class="typeBadge[item.type as ReleaseNoteItemType]?.class"
                                            >
                                                {{ typeBadge[item.type as ReleaseNoteItemType]?.label }}
                                            </span>
                                            <span class="text-muted-foreground">
                                                {{ item.description || '...' }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
