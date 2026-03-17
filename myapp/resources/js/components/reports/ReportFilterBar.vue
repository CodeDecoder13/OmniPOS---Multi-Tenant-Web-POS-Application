<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Branch, ReportFilters } from '@/types';

const props = defineProps<{
    filters: ReportFilters;
    branches: Pick<Branch, 'id' | 'name'>[];
}>();

const emit = defineEmits<{
    apply: [filters: { date_from: string; date_to: string; period: string; branch_id: number | null }];
}>();

const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);
const period = ref(props.filters.period);
const branchId = ref(props.filters.branch_id ? String(props.filters.branch_id) : 'all');

watch(() => props.filters, (f) => {
    dateFrom.value = f.date_from;
    dateTo.value = f.date_to;
    period.value = f.period;
    branchId.value = f.branch_id ? String(f.branch_id) : 'all';
});

function apply() {
    emit('apply', {
        date_from: dateFrom.value,
        date_to: dateTo.value,
        period: period.value,
        branch_id: branchId.value !== 'all' ? Number(branchId.value) : null,
    });
}
</script>

<template>
    <div class="flex flex-wrap items-end gap-4">
        <div class="space-y-1">
            <label class="text-xs font-medium text-muted-foreground">From</label>
            <Input type="date" v-model="dateFrom" class="w-[160px]" />
        </div>
        <div class="space-y-1">
            <label class="text-xs font-medium text-muted-foreground">To</label>
            <Input type="date" v-model="dateTo" class="w-[160px]" />
        </div>
        <div class="space-y-1">
            <label class="text-xs font-medium text-muted-foreground">Period</label>
            <Select v-model="period">
                <SelectTrigger class="w-[140px]">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="daily">Daily</SelectItem>
                    <SelectItem value="weekly">Weekly</SelectItem>
                    <SelectItem value="monthly">Monthly</SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div v-if="branches.length > 1" class="space-y-1">
            <label class="text-xs font-medium text-muted-foreground">Branch</label>
            <Select v-model="branchId">
                <SelectTrigger class="w-[180px]">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Branches</SelectItem>
                    <SelectItem v-for="branch in branches" :key="branch.id" :value="String(branch.id)">
                        {{ branch.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
        <Button @click="apply">Apply</Button>
    </div>
</template>
