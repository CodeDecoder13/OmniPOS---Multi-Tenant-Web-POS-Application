<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Lock, Square, User } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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

// Lock body scroll while POS layout is active
onMounted(() => {
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
});
onUnmounted(() => {
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
});
</script>

<template>
    <div class="flex h-screen flex-col bg-muted overflow-hidden">
        <header class="flex h-14 md:h-16 shrink-0 items-center justify-between border-b bg-card/95 backdrop-blur-sm shadow-sm px-3 md:px-6">
            <div class="flex items-center gap-2 md:gap-3">
                <Link :href="tenantUrl('dashboard')" class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors">
                    <ArrowLeft class="h-4 w-4" />
                    <span class="hidden sm:inline">Exit POS</span>
                </Link>
                <div class="hidden sm:block h-5 w-px bg-border" />
                <div class="flex items-center gap-2">
                    <Avatar class="h-7 w-7">
                        <AvatarFallback class="bg-primary/10 text-primary text-xs font-bold">{{ tenant?.name?.charAt(0)?.toUpperCase() ?? 'S' }}</AvatarFallback>
                    </Avatar>
                    <span class="hidden md:inline font-semibold text-sm">{{ tenant?.name }}</span>
                </div>
                <div class="hidden md:block h-5 w-px bg-border" />
                <span class="hidden md:inline text-xs text-muted-foreground font-mono">{{ currentTime }}</span>
            </div>
            <div v-if="isAuthenticated" class="flex items-center gap-1.5 md:gap-3">
                <!-- Shift info -->
                <div v-if="hasActiveShift" class="hidden md:flex items-center gap-2 rounded-full bg-muted px-3 py-1.5">
                    <Clock class="h-3.5 w-3.5 text-green-600" />
                    <span class="text-xs font-medium text-green-600">{{ shiftDuration }}</span>
                    <div class="h-3 w-px bg-border" />
                    <span class="hidden lg:inline text-xs text-muted-foreground">{{ shiftSummary?.total_orders ?? 0 }} orders | ₱{{ (shiftSummary?.total_sales ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
                </div>
                <Button v-if="hasActiveShift" variant="outline" size="sm" @click="handleEndShift" class="h-9 w-9 p-0 md:h-auto md:w-auto md:px-3 text-orange-600 border-orange-200 hover:bg-orange-50 dark:border-orange-800 dark:hover:bg-orange-950">
                    <Square class="h-3.5 w-3.5 md:mr-1.5" />
                    <span class="hidden md:inline">End Shift</span>
                </Button>
                <div class="flex items-center gap-2 rounded-full bg-muted px-2 py-1.5 md:px-3">
                    <User class="h-3.5 w-3.5 text-muted-foreground" />
                    <span class="hidden sm:inline text-xs font-medium max-w-[80px] md:max-w-none truncate">{{ operatorName }}</span>
                </div>
                <Button variant="outline" size="sm" @click="handleLogout" class="h-9 w-9 p-0 md:h-auto md:w-auto md:px-3">
                    <Lock class="h-3.5 w-3.5 md:mr-1.5" />
                    <span class="hidden md:inline">Switch User</span>
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
