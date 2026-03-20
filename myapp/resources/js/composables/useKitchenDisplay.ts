import { ref, computed, onUnmounted } from 'vue';
import { useTenant } from '@/composables/useTenant';
import type { Order, KitchenStatus } from '@/types';

export function useKitchenDisplay(initialOrders: Order[] = []) {
    const { tenantUrl } = useTenant();

    const orders = ref<Order[]>([...initialOrders]);
    const audioEnabled = ref(true);
    const lastPollTimestamp = ref<string | null>(null);
    let pollInterval: ReturnType<typeof setInterval> | null = null;

    // Audio alert using Web Audio API
    function playBeep() {
        if (!audioEnabled.value) return;
        try {
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            osc.type = 'square';
            gain.gain.value = 0.3;
            osc.start();
            osc.stop(ctx.currentTime + 0.2);
            // Second beep
            setTimeout(() => {
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.frequency.value = 1100;
                osc2.type = 'square';
                gain2.gain.value = 0.3;
                osc2.start();
                osc2.stop(ctx.currentTime + 0.2);
            }, 250);
        } catch {
            // Web Audio not supported
        }
    }

    const newOrders = computed(() => orders.value.filter(o => o.kitchen_status === 'new'));
    const preparingOrders = computed(() => orders.value.filter(o => o.kitchen_status === 'preparing'));
    const readyOrders = computed(() => orders.value.filter(o => o.kitchen_status === 'ready'));

    async function poll() {
        try {
            const params = new URLSearchParams();
            if (lastPollTimestamp.value) {
                params.set('since', lastPollTimestamp.value);
            }

            const res = await fetch(`${tenantUrl('kitchen/poll')}?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!res.ok) return;

            const data = await res.json();
            lastPollTimestamp.value = data.timestamp;

            const updatedOrders: Order[] = data.orders;
            let hasNewOrders = false;

            for (const updated of updatedOrders) {
                const idx = orders.value.findIndex(o => o.id === updated.id);
                const status = updated.kitchen_status as string | null;

                if (status === 'served') {
                    // Remove served orders from display
                    if (idx !== -1) {
                        orders.value.splice(idx, 1);
                    }
                } else if (idx !== -1) {
                    // Update existing order
                    orders.value[idx] = updated;
                } else if (status) {
                    // New order
                    orders.value.push(updated);
                    if (status === 'new') {
                        hasNewOrders = true;
                    }
                }
            }

            if (hasNewOrders) {
                playBeep();
            }
        } catch {
            // Silently fail on poll errors
        }
    }

    function startPolling() {
        if (pollInterval) return;
        pollInterval = setInterval(poll, 5000);
    }

    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    async function updateOrderStatus(orderId: number, status: KitchenStatus, notes?: string) {
        try {
            const xsrfToken = decodeURIComponent(
                document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
            );

            const body: Record<string, string> = { kitchen_status: status };
            if (notes) body.kitchen_notes = notes;

            const res = await fetch(tenantUrl(`kitchen/${orderId}/status`), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken,
                },
                body: JSON.stringify(body),
            });

            if (!res.ok) return;

            const data = await res.json();
            const updated: Order = data.order;

            const idx = orders.value.findIndex(o => o.id === orderId);
            if (updated.kitchen_status === 'served') {
                if (idx !== -1) orders.value.splice(idx, 1);
            } else if (idx !== -1) {
                orders.value[idx] = updated;
            }
        } catch {
            // Handle error silently
        }
    }

    function toggleAudio() {
        audioEnabled.value = !audioEnabled.value;
    }

    onUnmounted(stopPolling);

    return {
        orders,
        newOrders,
        preparingOrders,
        readyOrders,
        audioEnabled,
        startPolling,
        stopPolling,
        updateOrderStatus,
        toggleAudio,
    };
}
