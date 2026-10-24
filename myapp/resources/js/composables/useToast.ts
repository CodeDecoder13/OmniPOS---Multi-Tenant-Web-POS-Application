import { reactive } from 'vue';

export interface ToastItem {
    id: number;
    type: 'success' | 'error' | 'info';
    message: string;
    action?: { label: string; onClick: () => void };
}

interface ToastOptions {
    action?: ToastItem['action'];
    /** ms before auto-dismiss. Errors stay longer by default. */
    duration?: number;
}

const toasts = reactive<ToastItem[]>([]);
let seq = 0;

export function dismissToast(id: number) {
    const i = toasts.findIndex((t) => t.id === id);
    if (i > -1) toasts.splice(i, 1);
}

function push(type: ToastItem['type'], message: string, opts: ToastOptions = {}) {
    const id = ++seq;
    toasts.push({ id, type, message, action: opts.action });
    setTimeout(() => dismissToast(id), opts.duration ?? (type === 'error' ? 8000 : 5000));
    return id;
}

/** Call from anywhere: `toast.error('Checkout failed')`. Rendered by FlashMessage. */
export const toast = {
    success: (message: string, opts?: ToastOptions) => push('success', message, opts),
    error: (message: string, opts?: ToastOptions) => push('error', message, opts),
    info: (message: string, opts?: ToastOptions) => push('info', message, opts),
};

export function useToasts() {
    return { toasts, dismissToast };
}
