<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Crown, ExternalLink } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import NavUser from '@/components/NavUser.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { useTenant } from '@/composables/useTenant';
import { useTenantNav } from '@/composables/useTenantNav';
import type { NavItem } from '@/composables/useTenantNav';

const { t } = useI18n();
const { tenant, tenantUrl } = useTenant();
const { sections, isActive } = useTenantNav();

const planNames = (slugs: string[]) => slugs.map((s) => s.charAt(0).toUpperCase() + s.slice(1)).join(' / ');

function tooltipFor(item: NavItem): string {
    if (item.state === 'locked') return `${item.label}: ${t('nav.lockedHint', { plans: planNames(item.plans) })}`;
    if (item.state === 'disabled') return `${item.label}: ${t('nav.disabledHint')}`;
    return item.external ? `${item.label} (${t('nav.opensNewTab')})` : item.label;
}

// Plan-locked items open this prompt instead of navigating to a 403 page.
const lockedItem = ref<NavItem | null>(null);
const lockedOpen = computed({
    get: () => lockedItem.value !== null,
    set: (v) => { if (!v) lockedItem.value = null; },
});
const upgradeMailto = 'mailto:support@omnipos.shop?subject=Upgrade%20my%20OmniPOS%20plan';
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" :aria-label="t('nav.mainNav')">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="tenantUrl('dashboard')">
                            <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-gradient-to-br from-teal-400 to-teal-600 text-white shadow-lg shadow-teal-500/25">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="ml-1 grid flex-1 text-left text-sm">
                                <span class="mb-0.5 truncate leading-tight font-semibold">{{ tenant?.name }}</span>
                                <Badge v-if="tenant?.subscription?.plan" variant="outline" class="w-fit text-[10px] px-1.5 py-0">
                                    {{ tenant.subscription.plan.name }}
                                </Badge>
                                <span v-else class="truncate text-xs text-muted-foreground">No Plan</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="overflow-x-hidden">
            <template v-for="(section, i) in sections" :key="section.key">
                <SidebarSeparator v-if="i > 0 && !section.title" />
                <SidebarGroup>
                    <SidebarGroupLabel v-if="section.title">{{ section.title }}</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem v-for="item in section.items" :key="item.key">
                                <!-- Locked by plan: dimmed, crown, opens the upgrade prompt -->
                                <SidebarMenuButton
                                    v-if="item.state === 'locked'"
                                    :tooltip="tooltipFor(item)"
                                    class="opacity-70"
                                    @click="lockedItem = item"
                                >
                                    <component :is="item.icon" />
                                    <span>{{ item.label }}</span>
                                    <Crown class="ml-auto size-3.5 shrink-0 text-amber-500" aria-hidden="true" />
                                </SidebarMenuButton>

                                <!-- Turned off for this branch: dimmed, explains why on hover -->
                                <SidebarMenuButton
                                    v-else-if="item.state === 'disabled'"
                                    :tooltip="tooltipFor(item)"
                                    aria-disabled="true"
                                    class="cursor-not-allowed opacity-50 hover:bg-transparent"
                                >
                                    <component :is="item.icon" />
                                    <span>{{ item.label }}</span>
                                </SidebarMenuButton>

                                <SidebarMenuButton v-else as-child :is-active="isActive(item.path)" :tooltip="tooltipFor(item)">
                                    <a v-if="item.external" :href="tenantUrl(item.path)" target="_blank" rel="noopener">
                                        <component :is="item.icon" />
                                        <span>{{ item.label }}</span>
                                        <ExternalLink class="ml-auto size-3 shrink-0 opacity-50" aria-hidden="true" />
                                    </a>
                                    <Link v-else :href="tenantUrl(item.path)">
                                        <component :is="item.icon" />
                                        <span>{{ item.label }}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </template>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    
    </Sidebar>

    <Dialog v-model:open="lockedOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader v-if="lockedItem">
                <DialogTitle class="flex items-center gap-2">
                    <Crown class="size-5 text-amber-500" aria-hidden="true" />
                    {{ t('nav.lockedTitle', { feature: lockedItem.label }) }}
                </DialogTitle>
                <DialogDescription>
                    {{ t('nav.lockedBody', { plans: planNames(lockedItem.plans) }) }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="lockedItem = null">{{ t('nav.close') }}</Button>
                <Button as-child class="bg-amber-500 text-white hover:bg-amber-600">
                    <a :href="upgradeMailto">{{ t('nav.requestUpgrade') }}</a>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
