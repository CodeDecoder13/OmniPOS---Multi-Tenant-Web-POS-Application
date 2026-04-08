<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Monitor, Moon, Palette, Sun } from 'lucide-vue-next';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import TenantLayout from '@/layouts/TenantLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';
import type { BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: edit(),
    },
];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

        <h1 class="sr-only">Appearance settings</h1>

        <SettingsLayout>
            <!-- Theme Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                            <Palette class="h-4.5 w-4.5 text-primary" />
                        </div>
                        <div>
                            <CardTitle class="text-base">Theme</CardTitle>
                            <CardDescription>Choose how the app looks to you</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <AppearanceTabs />
                </CardContent>
            </Card>
        </SettingsLayout>
    </TenantLayout>
</template>
