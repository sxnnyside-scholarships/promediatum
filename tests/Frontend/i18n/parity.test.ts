import { describe, expect, it } from 'bun:test';
import en from '@/i18n/locales/en';
import es from '@/i18n/locales/es';

describe('i18n Locale Parity', () => {
    const esKeys = Object.keys(es).sort();
    const enKeys = Object.keys(en).sort();

    it('has identical number of keys in es and en', () => {
        expect(esKeys.length).toBeGreaterThan(0);
        expect(enKeys.length).toBeGreaterThan(0);
    });

    it('has no missing keys in English that exist in Spanish', () => {
        const missingInEn = esKeys.filter((key) => !(key in en));
        expect(missingInEn).toEqual([]);
    });

    it('has no missing keys in Spanish that exist in English', () => {
        const missingInEs = enKeys.filter((key) => !(key in es));
        expect(missingInEs).toEqual([]);
    });

    it('all keys contain non-empty string values', () => {
        for (const [key, value] of Object.entries(es)) {
            expect(typeof value).toBe('string');
            expect(value.trim().length).toBeGreaterThan(0);
        }

        for (const [key, value] of Object.entries(en)) {
            expect(typeof value).toBe('string');
            expect(value.trim().length).toBeGreaterThan(0);
        }
    });
});
