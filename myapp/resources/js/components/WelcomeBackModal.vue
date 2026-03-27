<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { DollarSign, ShoppingCart, Package, Users } from 'lucide-vue-next';
import { useCurrency } from '@/composables/useCurrency';

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
}>();

const emit = defineEmits<{
    close: [];
}>();

const { formatCurrency } = useCurrency();
</script>

<template>
    <Dialog :open="show" @update:open="emit('close')">
        <DialogContent :show-close-button="false" class="sm:max-w-md">
            <DialogHeader class="text-center sm:text-center">
                <DialogTitle class="text-2xl font-bold">
                    👋 Welcome back, {{ userName }}!
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

            <DialogFooter class="sm:justify-center">
                <Button class="w-full sm:w-auto" @click="emit('close')">
                    Go to Dashboard
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
