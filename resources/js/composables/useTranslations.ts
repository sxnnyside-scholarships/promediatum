import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import translations from '@/i18n/translations';

export function useTranslations() {
    const page = usePage();

    const locale = computed<string>(() => (page.props.locale as string) || 'es');

    function t(key: string, replacements: Record<string, string | number> = {}): string {
        const lang = locale.value;
        let text = translations[lang]?.[key] || translations.es?.[key] || key;

        // Simple replacement: :key → value
        Object.entries(replacements).forEach(([k, v]) => {
            text = text.replace(`:${k}`, String(v));
        });

        return text;
    }

    return { t, locale };
}
