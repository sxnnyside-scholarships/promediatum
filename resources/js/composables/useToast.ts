export type ToastType = 'success' | 'error' | 'info' | 'warning';

export interface ToastDetail {
    type: ToastType;
    message: string;
    duration: number;
}

/**
 * useToast — Composable for dispatching toast notifications.
 *
 * Fires a custom 'cp-toast' event consumed by CpToast.vue.
 * Works anywhere in the Vue app without needing provide/inject.
 */
export function useToast() {
    function dispatch(type: ToastType, message: string, duration = 4000): void {
        if (typeof window !== 'undefined') {
            window.dispatchEvent(
                new CustomEvent<ToastDetail>('cp-toast', {
                    detail: { type, message, duration },
                }),
            );
        }
    }

    return {
        success: (message: string, duration?: number) => dispatch('success', message, duration),
        error: (message: string, duration?: number) => dispatch('error', message, duration),
        info: (message: string, duration?: number) => dispatch('info', message, duration),
        warning: (message: string, duration?: number) => dispatch('warning', message, duration),
    };
}
