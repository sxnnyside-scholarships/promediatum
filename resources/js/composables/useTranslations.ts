import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import translations, { type SupportedLocale, type TranslationKey } from '@/i18n/translations';

export type { SupportedLocale, TranslationKey };

export type TranslationReplacements = Record<string, string | number>;

export function useTranslations() {
    const locale = computed<SupportedLocale>(() => {
        try {
            const page = usePage();
            const current = (page?.props?.locale as SupportedLocale) || 'es';
            return current in translations ? current : 'es';
        } catch {
            return 'es';
        }
    });

    /**
     * Translates a given key with support for token replacements (:key → value).
     * Strongly typed against all registered translation keys with autocomplete support.
     */
    function t(
        key: TranslationKey | (string & {}),
        replacements: TranslationReplacements = {},
    ): string {
        const lang = locale.value;
        const dict = translations[lang] ?? translations.es;
        let text: string =
            (dict as Record<string, string>)[key] ??
            (translations.es as Record<string, string>)[key] ??
            key;

        // Token replacement: :key → value
        Object.entries(replacements).forEach(([k, v]) => {
            text = text.replace(`:${k}`, String(v));
        });

        return text;
    }

    return { t, locale };
}
