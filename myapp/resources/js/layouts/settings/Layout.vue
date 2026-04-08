<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import { Palette, Shield, User } from 'lucide-vue-next';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

interface SettingsNavItem extends NavItem {
    icon: LucideIcon;
    description: string;
}

const sidebarNavItems: SettingsNavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
        icon: User,
        description: 'Name & email',
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: Shield,
        description: 'Password & 2FA',
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: Palette,
        description: 'Theme',
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight">Settings</h1>
            <p class="mt-1 text-sm text-muted-foreground">Manage your account preferences</p>
        </div>

        <div class="mb-8 flex gap-1 overflow-x-auto rounded-lg border bg-muted/40 p-1">
            <Link
                v-for="item in sidebarNavItems"
                :key="toUrl(item.href)"
                :href="item.href"
                prefetch
                :class="[
                    'flex items-center gap-2 rounded-md px-4 py-2.5 text-sm font-medium transition-all whitespace-nowrap',
                    isCurrentOrParentUrl(item.href)
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:bg-background/50 hover:text-foreground',
                ]"
            >
                <component :is="item.icon" class="h-4 w-4 shrink-0" />
                <span>{{ item.title }}</span>
            </Link>
        </div>

        <div class="space-y-6">
            <slot />
        </div>
    </div>
</template>
