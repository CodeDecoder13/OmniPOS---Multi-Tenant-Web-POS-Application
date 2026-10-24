<script setup lang="ts">
import { useEventListener } from '@vueuse/core';
import { Search } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import CommandPalette from '@/components/CommandPalette.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useLocale } from '@/composables/useLocale';
import { useTenant } from '@/composables/useTenant';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { initLocale } = useLocale();
const { t } = useI18n();
const paletteOpen = ref(false);
const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform);

// Ctrl/Cmd+K toggles the palette from anywhere.
useEventListener(window, 'keydown', (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        paletteOpen.value = !paletteOpen.value;
    }
});
const { tenant } = useTenant();
onMounted(() => initLocale());
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-white/20 dark:border-white/[0.06] backdrop-blur-md bg-white/50 dark:bg-white/[0.02] px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-1">
            <template v-if="tenant">
                <button
                    type="button"
                    class="hidden h-8 items-center gap-2 rounded-md border bg-background/60 px-2.5 text-sm text-muted-foreground outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:flex"
                    :aria-label="t('nav.search')"
                    @click="paletteOpen = true"
                >
                    <Search class="size-3.5" aria-hidden="true" />
                    <span>{{ t('nav.search') }}</span>
                    <kbd class="rounded border bg-muted px-1.5 text-[10px] font-medium">{{ isMac ? '⌘' : 'Ctrl' }} K</kbd>
                </button>
                <button
                    type="button"
                    class="flex size-8 items-center justify-center rounded-md text-muted-foreground outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:hidden"
                    :aria-label="t('nav.search')"
                    @click="paletteOpen = true"
                >
                    <Search class="size-4" aria-hidden="true" />
                </button>
                <CommandPalette v-model:open="paletteOpen" />
            </template>
            <NotificationBell v-if="tenant" />
            <AppearanceToggle />
            <LanguageSwitcher />
        </div>
    </header>
</template>
