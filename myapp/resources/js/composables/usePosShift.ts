import { ref, computed } from 'vue';
import { useTenant } from '@/composables/useTenant';
import type { Shift, ShiftSummary } from '@/types/models';

const activeShift = ref<Shift | null>(null);
const shiftSummary = ref<ShiftSummary | null>(null);
const now = ref(Date.now());
const currentTime = ref(new Date().toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }));

// Module-level interval — ticks every second for real-time clock + duration
let tickInterval: ReturnType<typeof setInterval> | null = null;

function ensureTick() {
    if (!tickInterval) {
        tickInterval = setInterval(() => {
            now.value = Date.now();
            currentTime.value = new Date().toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        }, 1000);
    }
}

// Start ticking immediately
ensureTick();

export function usePosShift() {
    const { tenantUrl } = useTenant();

    const hasActiveShift = computed(() => activeShift.value !== null);
    const shiftId = computed(() => activeShift.value?.id ?? null);

    const shiftDuration = computed(() => {
        if (!activeShift.value?.opened_at) return '';
        const start = new Date(activeShift.value.opened_at).getTime();
        const diff = Math.floor((now.value - start) / 1000);
        const hours = Math.floor(diff / 3600);
        const minutes = Math.floor((diff % 3600) / 60);
        const seconds = diff % 60;
        return `${hours}h ${minutes}m ${seconds}s`;
    });

    function getXsrfToken(): string {
        return decodeURIComponent(
            document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
        );
    }

    async function checkShiftStatus(userId: number): Promise<void> {
        try {
            const res = await fetch(`${tenantUrl('pos/shifts/status')}?user_id=${userId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (res.ok) {
                const data = await res.json();
                activeShift.value = data.shift;
                shiftSummary.value = data.summary;
                if (data.shift) ensureTick();
            }
        } catch {
            // silently fail
        }
    }

    async function openShift(userId: number, startingCash: number): Promise<{ success: boolean; message?: string }> {
        try {
            const res = await fetch(tenantUrl('pos/shifts/open'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                body: JSON.stringify({ user_id: userId, starting_cash: startingCash }),
            });

            if (!res.ok) {
                const err = await res.json();
                return { success: false, message: err.message ?? 'Failed to open shift.' };
            }

            const data = await res.json();
            activeShift.value = data.shift;
            shiftSummary.value = { total_sales: 0, total_orders: 0, avg_order_value: 0, voided_count: 0, payment_breakdown: [] };
            ensureTick();
            return { success: true };
        } catch {
            return { success: false, message: 'Network error. Please try again.' };
        }
    }

    async function closeShift(endingCash: number, notes?: string): Promise<{ success: boolean; shift?: Shift; summary?: ShiftSummary; message?: string }> {
        if (!activeShift.value) {
            return { success: false, message: 'No active shift.' };
        }

        try {
            const res = await fetch(tenantUrl('pos/shifts/close'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                body: JSON.stringify({
                    shift_id: activeShift.value.id,
                    ending_cash: endingCash,
                    notes: notes || null,
                }),
            });

            if (!res.ok) {
                const err = await res.json();
                return { success: false, message: err.message ?? 'Failed to close shift.' };
            }

            const data = await res.json();
            const closedShift = data.shift;
            const closedSummary = data.summary;
            activeShift.value = null;
            shiftSummary.value = null;
            return { success: true, shift: closedShift, summary: closedSummary };
        } catch {
            return { success: false, message: 'Network error. Please try again.' };
        }
    }

    async function refreshSummary(): Promise<void> {
        if (!activeShift.value) return;
        const userId = activeShift.value.user_id;
        await checkShiftStatus(userId);
    }

    function clearShift(): void {
        activeShift.value = null;
        shiftSummary.value = null;
    }

    return {
        activeShift,
        shiftSummary,
        hasActiveShift,
        shiftId,
        shiftDuration,
        currentTime,
        checkShiftStatus,
        openShift,
        closeShift,
        refreshSummary,
        clearShift,
    };
}
