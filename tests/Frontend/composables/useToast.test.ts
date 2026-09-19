import { describe, expect, it } from 'bun:test';
import { useToast, type ToastDetail } from '@/composables/useToast';

describe('useToast Composable', () => {
    it('dispatches custom event for success toast', () => {
        let dispatchedEvent: CustomEvent<ToastDetail> | null = null;

        const listener = (e: Event) => {
            dispatchedEvent = e as CustomEvent<ToastDetail>;
        };

        window.addEventListener('cp-toast', listener);

        const toast = useToast();
        toast.success('Backup completed successfully!', 3000);

        window.removeEventListener('cp-toast', listener);

        expect(dispatchedEvent).not.toBeNull();
        expect(dispatchedEvent!.detail.type).toBe('success');
        expect(dispatchedEvent!.detail.message).toBe('Backup completed successfully!');
        expect(dispatchedEvent!.detail.duration).toBe(3000);
    });

    it('dispatches custom event for error toast with default duration', () => {
        let dispatchedEvent: CustomEvent<ToastDetail> | null = null;

        const listener = (e: Event) => {
            dispatchedEvent = e as CustomEvent<ToastDetail>;
        };

        window.addEventListener('cp-toast', listener);

        const toast = useToast();
        toast.error('Network failure');

        window.removeEventListener('cp-toast', listener);

        expect(dispatchedEvent).not.toBeNull();
        expect(dispatchedEvent!.detail.type).toBe('error');
        expect(dispatchedEvent!.detail.message).toBe('Network failure');
        expect(dispatchedEvent!.detail.duration).toBe(4000);
    });

    it('dispatches custom event for warning and info toasts', () => {
        const events: ToastDetail[] = [];

        const listener = (e: Event) => {
            events.push((e as CustomEvent<ToastDetail>).detail);
        };

        window.addEventListener('cp-toast', listener);

        const toast = useToast();
        toast.warning('Check input');
        toast.info('Session refreshed');

        window.removeEventListener('cp-toast', listener);

        expect(events.length).toBe(2);
        expect(events[0].type).toBe('warning');
        expect(events[1].type).toBe('info');
    });
});
