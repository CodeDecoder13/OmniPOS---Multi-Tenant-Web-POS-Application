import { ref, computed } from 'vue';
import { useTenant } from '@/composables/useTenant';
import type { PosOperator } from '@/types/models';

const posOperator = ref<PosOperator | null>(null);

export function usePosAuth() {
    const { tenantUrl } = useTenant();

    const isAuthenticated = computed(() => posOperator.value !== null);
    const operatorName = computed(() => posOperator.value?.name ?? '');
    const operatorUserId = computed(() => posOperator.value?.user_id ?? null);
    const operatorPermissions = computed(() => posOperator.value?.permissions ?? []);

    function operatorCan(slug: string): boolean {
        return posOperator.value?.permissions.includes(slug) ?? false;
    }

    async function loginWithPin(pin: string): Promise<{ success: boolean; message?: string }> {
        try {
            const xsrfToken = decodeURIComponent(
                document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
            );

            const res = await fetch(tenantUrl('pos/pin/verify'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken,
                },
                body: JSON.stringify({ pin }),
            });

            if (!res.ok) {
                const err = await res.json();
                return { success: false, message: err.message ?? 'Invalid PIN.' };
            }

            const data: PosOperator = await res.json();
            posOperator.value = data;
            return { success: true };
        } catch {
            return { success: false, message: 'Network error. Please try again.' };
        }
    }

    function logout() {
        posOperator.value = null;
    }

    return {
        posOperator,
        isAuthenticated,
        operatorName,
        operatorUserId,
        operatorPermissions,
        operatorCan,
        loginWithPin,
        logout,
    };
}
