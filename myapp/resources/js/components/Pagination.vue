<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

interface PaginatedData {
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    data: PaginatedData;
}>();
</script>

<template>
    <div v-if="data.last_page > 1" class="flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
            Showing {{ data.from }} to {{ data.to }} of {{ data.total }} results
        </p>
        <div class="flex items-center gap-1">
            <template v-for="(link, i) in data.links" :key="i">
                <Button
                    v-if="link.url"
                    variant="outline"
                    size="sm"
                    as-child
                    :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
                >
                    <Link :href="link.url" preserve-state v-html="link.label" />
                </Button>
                <Button
                    v-else
                    variant="outline"
                    size="sm"
                    disabled
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
