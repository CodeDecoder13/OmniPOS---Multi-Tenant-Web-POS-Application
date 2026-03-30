<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { DollarSign, ShoppingCart, Package, Users, Sparkles } from 'lucide-vue-next';
import { useCurrency } from '@/composables/useCurrency';
import type { ReleaseNote, ReleaseNoteItemType } from '@/types/models';

defineProps<{
    show: boolean;
    userName: string;
    tenantName: string;
    stats: {
        todayRevenue: number;
        todayOrderCount: number;
        productsCount: number;
        usersCount: number;
    };
    releaseNotes?: ReleaseNote[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const { formatCurrency } = useCurrency();

const typeBadge: Record<ReleaseNoteItemType, { label: string; class: string }> = {
    feature: { label: 'NEW', class: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400' },
    fix: { label: 'FIX', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    improvement: { label: 'IMP', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
};
</script>

<template>
    <Dialog :open="show" @update:open="emit('close')">
        <DialogContent :show-close-button="false" class="sm:max-w-lg">
            <DialogHeader class="text-center sm:text-center">
                <DialogTitle class="text-2xl font-bold">
                    Welcome back, {{ userName }}!
                </DialogTitle>
                <DialogDescription class="text-sm text-muted-foreground">
                    Here's a quick look at <span class="font-medium text-foreground">{{ tenantName }}</span> today.
                </DialogDescription>
            </DialogHeader>

            <div class="grid grid-cols-2 gap-3 py-4">
                <!-- Today's Revenue -->
                <div class="flex items-center gap-3 rounded-xl border bg-teal-50/50 p-4 dark:border-teal-900/30 dark:bg-teal-900/10">
                    <div class="rounded-lg bg-teal-100 p-2 dark:bg-teal-900/30">
                        <DollarSign class="h-4 w-4 text-teal-600" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-lg font-bold">{{ formatCurrency(stats.todayRevenue) }}</p>
                        <p class="text-xs text-muted-foreground">Today's Revenue</p>
                    </div>
                </div>

                <!-- Today's Orders -->
                <div class="flex items-center gap-3 rounded-xl border bg-blue-50/50 p-4 dark:border-blue-900/30 dark:bg-blue-900/10">
                    <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                        <ShoppingCart class="h-4 w-4 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-lg font-bold">{{ stats.todayOrderCount }}</p>
                        <p class="text-xs text-muted-foreground">Today's Orders</p>
                    </div>
                </div>

                <!-- Total Products -->
                <div class="flex items-center gap-3 rounded-xl border bg-orange-50/50 p-4 dark:border-orange-900/30 dark:bg-orange-900/10">
                    <div class="rounded-lg bg-orange-100 p-2 dark:bg-orange-900/30">
                        <Package class="h-4 w-4 text-orange-600" />
                    </div>
                    <div>
                        <p class="text-lg font-bold">{{ stats.productsCount }}</p>
                        <p class="text-xs text-muted-foreground">Products</p>
                    </div>
                </div>

                <!-- Team Members -->
                <div class="flex items-center gap-3 rounded-xl border bg-violet-50/50 p-4 dark:border-violet-900/30 dark:bg-violet-900/10">
                    <div class="rounded-lg bg-violet-100 p-2 dark:bg-violet-900/30">
                        <Users class="h-4 w-4 text-violet-600" />
                    </div>
                    <div>
                        <p class="text-lg font-bold">{{ stats.usersCount }}</p>
                        <p class="text-xs text-muted-foreground">Team Members</p>
                    </div>
                </div>
            </div>

            <!-- Release Notes Section -->
            <div v-if="releaseNotes?.length" class="border-t pt-4 dark:border-gray-800">
                <div class="mb-3 flex items-center gap-2">
                    <Sparkles class="h-4 w-4 text-amber-500" />
                    <h3 class="text-sm font-semibold">What's New</h3>
                </div>
                <div class="max-h-48 space-y-3 overflow-y-auto pr-1">
                    <div v-for="note in releaseNotes" :key="note.id" class="rounded-lg border bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-800/30">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="text-sm font-semibold">{{ note.title }}</span>
                            <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium dark:bg-gray-700">
                                v{{ note.version }}
                            </span>
                        </div>
                        <ul class="space-y-1">
                            <li v-for="(item, i) in note.items" :key="i" class="flex items-start gap-2 text-xs">
                                <span class="mt-0.5 shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold" :class="typeBadge[item.type]?.class">
                                    {{ typeBadge[item.type]?.label }}
                                </span>
                                <span class="text-muted-foreground">{{ item.description }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <DialogFooter class="sm:justify-center">
                <Button class="w-full sm:w-auto" @click="emit('close')">
                    Go to Dashboard
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
