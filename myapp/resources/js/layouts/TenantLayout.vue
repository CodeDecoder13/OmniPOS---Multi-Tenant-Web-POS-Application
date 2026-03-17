<script setup lang="ts">
import TenantLayout from '@/layouts/tenant/TenantSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <slot />
    </TenantLayout>
</template>
