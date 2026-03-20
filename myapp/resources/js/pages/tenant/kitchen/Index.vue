<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ChefHat, Volume2, VolumeX } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import KitchenOrderCard from '@/components/kitchen/KitchenOrderCard.vue';
import { useKitchenDisplay } from '@/composables/useKitchenDisplay';
import { useTenant } from '@/composables/useTenant';
import type { Order, KitchenStatus } from '@/types';

const props = defineProps<{
    orders: Order[];
    branchName: string;
    kitchenEnabled: boolean;
}>();

const { tenantUrl } = useTenant();

const {
    orders,
    newOrders,
    preparingOrders,
    readyOrders,
    audioEnabled,
    startPolling,
    updateOrderStatus,
    toggleAudio,
} = useKitchenDisplay(props.orders);

const totalActive = computed(() => orders.value.length);

function handleUpdateStatus(orderId: number, status: KitchenStatus) {
    updateOrderStatus(orderId, status);
}

onMounted(() => {
    if (props.kitchenEnabled) {
        startPolling();
    }
});
</script>

<template>
    <div class="flex h-screen flex-col bg-gray-900 text-white">
        <!-- Header -->
        <header class="flex h-14 shrink-0 items-center justify-between border-b border-gray-700 bg-gray-800 px-4">
            <div class="flex items-center gap-3">
                <Link :href="tenantUrl('dashboard')" class="flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
                    <ArrowLeft class="h-4 w-4" />
                    <span>Back</span>
                </Link>
                <div class="h-5 w-px bg-gray-600" />
                <div class="flex items-center gap-2">
                    <ChefHat class="h-5 w-5 text-orange-400" />
                    <span class="font-semibold text-sm">Kitchen Display</span>
                </div>
                <div class="h-5 w-px bg-gray-600" />
                <span class="text-sm text-gray-400">{{ branchName }}</span>
            </div>
            <div class="flex items-center gap-3">
                <!-- Order count badges -->
                <div class="flex items-center gap-2">
                    <Badge v-if="newOrders.length > 0" class="bg-red-500/20 text-red-400 border-red-500/50">
                        {{ newOrders.length }} New
                    </Badge>
                    <Badge v-if="preparingOrders.length > 0" class="bg-yellow-500/20 text-yellow-400 border-yellow-500/50">
                        {{ preparingOrders.length }} Preparing
                    </Badge>
                    <Badge v-if="readyOrders.length > 0" class="bg-green-500/20 text-green-400 border-green-500/50">
                        {{ readyOrders.length }} Ready
                    </Badge>
                    <Badge variant="outline" class="text-gray-400 border-gray-600">
                        {{ totalActive }} Total
                    </Badge>
                </div>
                <!-- Audio toggle -->
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    :class="audioEnabled ? 'text-green-400 hover:text-green-300' : 'text-gray-500 hover:text-gray-400'"
                    @click="toggleAudio"
                    :title="audioEnabled ? 'Mute alerts' : 'Enable alerts'"
                >
                    <Volume2 v-if="audioEnabled" class="h-4 w-4" />
                    <VolumeX v-else class="h-4 w-4" />
                </Button>
            </div>
        </header>

        <!-- Main content -->
        <main class="flex-1 overflow-y-auto p-4">
            <div v-if="!kitchenEnabled" class="flex h-full items-center justify-center">
                <div class="text-center">
                    <ChefHat class="h-16 w-16 text-gray-600 mx-auto mb-4" />
                    <h2 class="text-xl font-semibold text-gray-400">Kitchen Display Not Enabled</h2>
                    <p class="text-sm text-gray-500 mt-2">Enable the Kitchen Display System in your branch settings.</p>
                </div>
            </div>

            <div v-else-if="orders.length === 0" class="flex h-full items-center justify-center">
                <div class="text-center">
                    <ChefHat class="h-16 w-16 text-gray-600 mx-auto mb-4" />
                    <h2 class="text-xl font-semibold text-gray-400">No Active Orders</h2>
                    <p class="text-sm text-gray-500 mt-2">New orders will appear here automatically.</p>
                </div>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <KitchenOrderCard
                    v-for="order in orders"
                    :key="order.id"
                    :order="order"
                    @update-status="handleUpdateStatus"
                />
            </div>
        </main>
    </div>
</template>
