<script setup lang="ts">
import { ref } from 'vue';
import { Download, FileText, FileSpreadsheet } from 'lucide-vue-next';
import { useTenant } from '@/composables/useTenant';
import type { ReportFilters } from '@/types';

const props = defineProps<{
    type: string;
    filters: ReportFilters;
}>();

const { tenantUrl } = useTenant();
const open = ref(false);

function exportReport(format: 'csv' | 'pdf') {
    const params = new URLSearchParams({
        type: props.type,
        format,
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
        period: props.filters.period,
    });
    if (props.filters.branch_id) {
        params.set('branch_id', String(props.filters.branch_id));
    }
    window.location.href = tenantUrl(`reports/export?${params.toString()}`);
    open.value = false;
}

function toggleDropdown() {
    open.value = !open.value;
}

function closeDropdown() {
    open.value = false;
}
</script>

<template>
    <div class="relative" @mouseleave="closeDropdown">
        <button
            @click="toggleDropdown"
            class="inline-flex items-center gap-2 rounded-lg border bg-card px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-muted"
        >
            <Download class="h-4 w-4" />
            Export
        </button>
        <div
            v-if="open"
            class="absolute right-0 z-10 mt-1 w-44 overflow-hidden rounded-lg border bg-card shadow-lg"
        >
            <button
                @click="exportReport('csv')"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm transition-colors hover:bg-muted"
            >
                <FileSpreadsheet class="h-4 w-4 text-emerald-500" />
                Export CSV
            </button>
            <button
                @click="exportReport('pdf')"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm transition-colors hover:bg-muted"
            >
                <FileText class="h-4 w-4 text-red-500" />
                Export PDF
            </button>
        </div>
    </div>
</template>
