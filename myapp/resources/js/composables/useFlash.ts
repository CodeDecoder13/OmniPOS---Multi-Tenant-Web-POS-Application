import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Flash } from '@/types';

export function useFlash() {
    const page = usePage();

    const flash = computed<Flash>(() => (page.props.flash as Flash) ?? { success: null, error: null });

    return {
        flash,
    };
}
