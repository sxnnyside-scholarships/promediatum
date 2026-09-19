import en from './locales/en';
import es from './locales/es';

const translations = {
    es,
    en,
};

export type SupportedLocale = keyof typeof translations;
export type TranslationKey = keyof typeof es;

export { en, es };
export default translations;
