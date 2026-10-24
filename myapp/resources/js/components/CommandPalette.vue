<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CornerDownLeft, Plus, Search } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import type { Component } from 'vue';
import { useI18n } from 'vue-i18n';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { useTenantNav } from '@/composables/useTenantNav';
import type { NavItem } from '@/composables/useTenantNav';

/**
 * Ctrl/Cmd+K jump-to. Pages come from the same list as the sidebar, so it only
 * offers what the user can actually open. Typed text also jumps to a list page
 * with its ?search= filter applied.
 */
const open = defineModel<boolean>('open', { default: false });

const { t } = useI18n();
const { sections, tenantUrl, can } = useTenantNav();

interface Entry {
    id: string;
    label: string;
    hint: string;
    icon: Component;
    run: () => void;
}

const query = ref('');
const active = ref(0);
const listEl = ref<HTMLElement | null>(null);

const usable = computed(() =>
    sections.value.flatMap((s) => s.items.filter((i) => i.state === 'ok').map((i) => ({ item: i, section: s.title }))),
);

function go(item: NavItem, search?: string) {
    open.value = false;
    const url = tenantUrl(item.path) + (search ? `?search=${encodeURIComponent(search)}` : '');
    if (item.external) window.open(url, '_blank', 'noopener');
    else router.visit(url);
}

const pages = computed<Entry[]>(() =>
    usable.value.map(({ item, section }) => ({
        id: `page-${item.key}`,
        label: item.label,
        hint: section || t('nav.goTo'),
        icon: item.icon,
        run: () => go(item),
    })),
);

const quick = computed<Entry[]>(() => {
    const out: Entry[] = [];
    if (can('products.create')) {
        out.push({
            id: 'quick-product',
            label: t('nav.newProduct'),
            hint: t('nav.quickActions'),
            icon: Plus,
            run: () => {
                open.value = false;
                router.visit(tenantUrl('products/create'));
            },
        });
    }
    return out;
});

// List pages that understand ?search=
const searchable = ['orders', 'products', 'customers', 'inventory'];

const entries = computed<Entry[]>(() => {
    const q = query.value.trim();
    if (!q) return [...pages.value, ...quick.value];

    const needle = q.toLowerCase();
    const matches = [...pages.value, ...quick.value].filter((e) => e.label.toLowerCase().includes(needle));
    const searches = usable.value
        .filter(({ item }) => searchable.includes(item.key))
        .map<Entry>(({ item }) => ({
            id: `search-${item.key}`,
            label: t('nav.searchIn', { where: item.label, query: `"${q}"` }),
            hint: t('nav.search'),
            icon: Search,
            run: () => go(item, q),
        }));

    return [...matches, ...searches];
});

watch(entries, () => (active.value = 0));

watch(open, (v) => {
    if (v) {
        query.value = '';
        active.value = 0;
    }
});

function move(delta: number) {
    const n = entries.value.length;
    if (!n) return;
    active.value = (active.value + delta + n) % n;
    nextTick(() => listEl.value?.querySelector('[aria-selected="true"]')?.scrollIntoView({ block: 'nearest' }));
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        move(1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        move(-1);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        entries.value[active.value]?.run();
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <!-- Keyboard-triggered, used constantly: no open animation (duration-0). -->
        <DialogContent
            :show-close-button="false"
            class="top-[20%] translate-y-0 gap-0 overflow-hidden p-0 duration-0 sm:max-w-xl"
        >
            <DialogTitle class="sr-only">{{ t('nav.search') }}</DialogTitle>
            <DialogDescription class="sr-only">{{ t('nav.searchPlaceholder') }}</DialogDescription>

            <div class="flex items-center gap-3 border-b px-4">
                <Search class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                <input
                    v-model="query"
                    type="text"
                    role="combobox"
                    aria-expanded="true"
                    aria-controls="command-list"
                    :aria-activedescendant="entries[active] ? `cmd-${entries[active].id}` : undefined"
                    :placeholder="t('nav.searchPlaceholder')"
                    :aria-label="t('nav.search')"
                    class="h-12 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    autocomplete="off"
                    @keydown="onKeydown"
                />
            </div>

            <ul id="command-list" ref="listEl" role="listbox" class="max-h-80 overflow-y-auto p-2">
                <li v-if="!entries.length" class="px-3 py-6 text-center text-sm text-muted-foreground">
                    {{ t('nav.noResults') }}
                </li>
                <li
                    v-for="(entry, i) in entries"
                    :id="`cmd-${entry.id}`"
                    :key="entry.id"
                    role="option"
                    :aria-selected="i === active"
                    class="flex min-h-11 cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-sm"
                    :class="i === active ? 'bg-accent text-accent-foreground' : ''"
                    @mousemove="active = i"
                    @click="entry.run()"
                >
                    <component :is="entry.icon" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                    <span class="flex-1 truncate">{{ entry.label }}</span>
                    <span class="shrink-0 text-xs text-muted-foreground">{{ entry.hint }}</span>
                    <CornerDownLeft v-if="i === active" class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                </li>
            </ul>
        </DialogContent>
    </Dialog>
</template>
