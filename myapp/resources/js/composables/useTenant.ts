import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { SharedTenant, SharedTenantRole } from '@/types';

export function useTenant() {
    const page = usePage();

    const tenant = computed<SharedTenant | null>(() => page.props.tenant as SharedTenant | null);
    const tenantRole = computed<SharedTenantRole | null>(() => page.props.tenantRole as SharedTenantRole | null);

    const isOwner = computed(() => tenantRole.value?.slug === 'owner');
    const isAdmin = computed(() => tenantRole.value?.slug === 'admin' || tenantRole.value?.slug === 'owner');

    function tenantUrl(path: string): string {
        const slug = tenant.value?.slug ?? page.url.split('/').filter(Boolean)[0] ?? '';
        return `/${slug}/${path}`;
    }

    return {
        tenant,
        tenantRole,
        isOwner,
        isAdmin,
        tenantUrl,
    };
}
