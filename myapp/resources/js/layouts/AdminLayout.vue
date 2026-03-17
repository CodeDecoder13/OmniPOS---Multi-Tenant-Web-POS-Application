<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AdminLayout>
</template>
