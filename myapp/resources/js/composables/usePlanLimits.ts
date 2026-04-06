import { usePage } from '@inertiajs/vue3';
import { computed, type MaybeRefOrGetter, toValue } from 'vue';
import type { SharedTenant } from '@/types';

const BLOCKED_STATUSES = ['cancelled', 'expired', 'past_due'] as const;

export function usePlanLimits(resource: 'branches' | 'users' | 'products', currentCount: MaybeRefOrGetter<number>) {
    const page = usePage();
    const tenant = computed<SharedTenant | null>(() => page.props.tenant as SharedTenant | null);

    const subscriptionBlocked = computed(() => {
        const status = tenant.value?.subscription?.status;
        if (!status) return false;
        return (BLOCKED_STATUSES as readonly string[]).includes(status);
    });

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
        if (subscriptionBlocked.value) return true;
        if (maxLimit.value === null) return false;
        return toValue(currentCount) >= maxLimit.value;
    });

    const canCreate = computed(() => !limitReached.value);

    const limitMessage = computed(() => {
        if (subscriptionBlocked.value) {
            const status = tenant.value?.subscription?.status ?? '';
            const label = status.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
            return `Your subscription is ${label}. Please renew your subscription to create new resources.`;
        }
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
