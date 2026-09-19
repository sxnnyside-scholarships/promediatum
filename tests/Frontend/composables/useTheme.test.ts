import { afterAll, beforeAll, describe, expect, it } from 'bun:test';
import { useTheme } from '@/composables/useTheme';

describe('useTheme Composable', () => {
    const originalWarn = console.warn;
    beforeAll(() => {
        console.warn = (...args: any[]) => {
            if (typeof args[0] === 'string' && args[0].includes('[Vue warn]: onMounted')) return;
            originalWarn(...args);
        };
    });
    afterAll(() => {
        console.warn = originalWarn;
    });
    it('sets dark mode correctly', () => {
        const { isDark, mode, setMode } = useTheme();

        setMode('dark');
        expect(mode.value).toBe('dark');
        expect(isDark.value).toBe(true);
        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(localStorage.getItem('theme')).toBe('dark');
    });

    it('sets light mode correctly', () => {
        const { isDark, mode, setMode } = useTheme();

        setMode('light');
        expect(mode.value).toBe('light');
        expect(isDark.value).toBe(false);
        expect(document.documentElement.classList.contains('dark')).toBe(false);
        expect(localStorage.getItem('theme')).toBe('light');
    });

    it('toggles mode back and forth', () => {
        const { isDark, setMode, toggle } = useTheme();

        setMode('light');
        expect(isDark.value).toBe(false);

        toggle();
        expect(isDark.value).toBe(true);

        toggle();
        expect(isDark.value).toBe(false);
    });
});
