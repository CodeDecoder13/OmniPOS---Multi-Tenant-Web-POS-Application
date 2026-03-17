<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Lock, Square } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import FlashMessage from '@/components/FlashMessage.vue';
import EndShiftDialog from '@/components/EndShiftDialog.vue';
import ShiftSummaryDialog from '@/components/ShiftSummaryDialog.vue';
import { useTenant } from '@/composables/useTenant';
import { usePosAuth } from '@/composables/usePosAuth';
import { usePosShift } from '@/composables/usePosShift';
import type { Shift, ShiftSummary } from '@/types/models';

const { tenant, tenantUrl } = useTenant();
const { isAuthenticated, operatorName, logout } = usePosAuth();
const { hasActiveShift, shiftDuration, shiftSummary, currentTime, clearShift } = usePosShift();

const showEndShift = ref(false);
const showShiftSummary = ref(false);
const closedShift = ref<Shift | null>(null);
const closedSummary = ref<ShiftSummary | null>(null);

function handleEndShift() {
    showEndShift.value = true;
}

function handleShiftEnded(payload: { shift: Shift; summary: ShiftSummary }) {
    closedShift.value = payload.shift;
    closedSummary.value = payload.summary;
    showShiftSummary.value = true;
}

function handleLogout() {
    clearShift();
    logout();
}
</script>

<template>
    <div class="flex h-screen flex-col bg-background">
        <header class="flex h-14 shrink-0 items-center justify-between border-b bg-card px-4">
            <div class="flex items-center gap-3">
                <Link :href="tenantUrl('dashboard')" class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors">
                    <ArrowLeft class="h-4 w-4" />
                    <span>Exit POS</span>
                </Link>
                <div class="h-5 w-px bg-border" />
                <span class="font-semibold text-sm">{{ tenant?.name }}</span>
                <div class="h-5 w-px bg-border" />
                <span class="text-xs text-muted-foreground font-mono">{{ currentTime }}</span>
            </div>
            <div v-if="isAuthenticated" class="flex items-center gap-3">
                <!-- Shift info -->
                <div v-if="hasActiveShift" class="flex items-center gap-2 rounded-md bg-muted px-3 py-1.5">
                    <Clock class="h-3.5 w-3.5 text-green-600" />
                    <span class="text-xs font-medium text-green-600">{{ shiftDuration }}</span>
                    <div class="h-3 w-px bg-border" />
                    <span class="text-xs text-muted-foreground">{{ shiftSummary?.total_orders ?? 0 }} orders</span>
                </div>
                <Button v-if="hasActiveShift" variant="outline" size="sm" @click="handleEndShift" class="text-orange-600 border-orange-200 hover:bg-orange-50 dark:border-orange-800 dark:hover:bg-orange-950">
                    <Square class="mr-1.5 h-3.5 w-3.5" />
                    End Shift
                </Button>
                <span class="text-sm text-muted-foreground">
                    Operator: <span class="font-medium text-foreground">{{ operatorName }}</span>
                </span>
                <Button variant="outline" size="sm" @click="handleLogout">
                    <Lock class="mr-1.5 h-3.5 w-3.5" />
                    Switch User
                </Button>
            </div>
        </header>
        <FlashMessage />
        <main class="flex-1 overflow-hidden">
            <slot />
        </main>

        <EndShiftDialog v-model:open="showEndShift" @shift-ended="handleShiftEnded" />
        <ShiftSummaryDialog v-model:open="showShiftSummary" :shift="closedShift" :summary="closedSummary" />
    </div>
</template>
