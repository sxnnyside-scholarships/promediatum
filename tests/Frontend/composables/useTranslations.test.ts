import { describe, expect, it } from 'bun:test';
import { useTranslations } from '@/composables/useTranslations';

describe('useTranslations Composable', () => {
    it('resolves Spanish keys by default', () => {
        const { t, locale } = useTranslations();
        expect(locale.value).toBe('es');
        expect(t('auth.login')).toBe('Iniciar Sesión');
        expect(t('auth.logout')).toBe('Cerrar Sesión');
    });

    it('replaces tokens accurately', () => {
        const { t } = useTranslations();
        const text = t('backup.pruned_successfully', { count: 3 });
        expect(text).toContain('3');
    });

    it('returns the key verbatim if key does not exist', () => {
        const { t } = useTranslations();
        // @ts-expect-error Testing non-existent key fallback
        expect(t('non.existent.key')).toBe('non.existent.key');
    });
});
