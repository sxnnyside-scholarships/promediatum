<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { useTranslations } from '@/composables/useTranslations.js';

const { t, locale } = useTranslations();

function switchLocale(newLocale) {
    router.post(route('locale.update'), {
        locale: newLocale,
    }, {
        preserveState: false,
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="flex items-center gap-1">
        <button
            type="button"
            :class="[
                'px-2 py-1 text-xs font-medium rounded-subtle transition-colors duration-150',
                locale === 'es'
                    ? 'bg-cafe-200 text-cafe-800 dark:bg-surface-dark-3 dark:text-cafe-100'
                    : 'text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200',
            ]"
            @click="switchLocale('es')"
        >
            ES
        </button>
        <button
            type="button"
            :class="[
                'px-2 py-1 text-xs font-medium rounded-subtle transition-colors duration-150',
                locale === 'en'
                    ? 'bg-cafe-200 text-cafe-800 dark:bg-surface-dark-3 dark:text-cafe-100'
                    : 'text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200',
            ]"
            @click="switchLocale('en')"
        >
            EN
        </button>
    </div>
</template>
