import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    const permissions = computed<string[]>(() => (page.props.tenantPermissions as string[]) ?? []);

    function can(slug: string): boolean {
        return permissions.value.includes(slug);
    }

    function canAny(...slugs: string[]): boolean {
        return slugs.some((slug) => permissions.value.includes(slug));
    }

    function canAll(...slugs: string[]): boolean {
        return slugs.every((slug) => permissions.value.includes(slug));
    }

    return {
        permissions,
        can,
        canAny,
        canAll,
    };
}
