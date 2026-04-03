import { usePage } from '@inertiajs/vue3';
import { computed, type MaybeRefOrGetter, toValue } from 'vue';
import type { SharedTenant } from '@/types';

export function usePlanLimits(resource: 'branches' | 'users' | 'products', currentCount: MaybeRefOrGetter<number>) {
    const page = usePage();
    const tenant = computed<SharedTenant | null>(() => page.props.tenant as SharedTenant | null);

    const maxLimit = computed<number | null>(() => {
        const plan = tenant.value?.subscription?.plan;
        if (!plan) return null;

        const fieldMap = {
            branches: 'max_branches',
            users: 'max_users',
            products: 'max_products',
        } as const;

        return plan[fieldMap[resource]];
    });

    const limitReached = computed(() => {
        if (maxLimit.value === null) return false;
        return toValue(currentCount) >= maxLimit.value;
    });

    const canCreate = computed(() => !limitReached.value);

    const limitMessage = computed(() => {
        if (!limitReached.value) return '';
        const plan = tenant.value?.subscription?.plan;
        return `Your ${plan?.name ?? ''} plan allows max ${maxLimit.value} ${resource}. Upgrade to add more.`;
    });

    return {
        canCreate,
        limitReached,
        limitMessage,
        maxLimit,
    };
}
