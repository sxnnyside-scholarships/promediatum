/**
 * useToast — Composable for dispatching toast notifications.
 *
 * Fires a custom 'cp-toast' event consumed by CpToast.vue.
 * Works anywhere in the Vue app without needing provide/inject.
 *
 * Usage:
 *   import { useToast } from '@/composables/useToast.js';
 *   const toast = useToast();
 *   toast.success('Saved!');
 *   toast.error('Something went wrong.');
 */
export function useToast() {
    function dispatch(type, message, duration = 4000) {
        window.dispatchEvent(new CustomEvent('cp-toast', {
            detail: { type, message, duration },
        }));
    }

    return {
        success: (message, duration) => dispatch('success', message, duration),
        error:   (message, duration) => dispatch('error', message, duration),
        info:    (message, duration) => dispatch('info', message, duration),
        warning: (message, duration) => dispatch('warning', message, duration),
    };
}
