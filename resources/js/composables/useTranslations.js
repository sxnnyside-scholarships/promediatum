import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import translations from '@/i18n/translations.js';

export function useTranslations() {
    const page = usePage();

    const locale = computed(() => page.props.locale || 'es');

    function t(key, replacements = {}) {
        const lang = locale.value;
        let text = translations[lang]?.[key] || translations['es']?.[key] || key;

        // Simple replacement: :key → value
        Object.entries(replacements).forEach(([k, v]) => {
            text = text.replace(`:${k}`, v);
        });

        return text;
    }

    return { t, locale };
}
