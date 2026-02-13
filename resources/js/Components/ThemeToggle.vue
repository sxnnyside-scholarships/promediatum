<script setup>
import { useTheme } from '@/composables/useTheme.js';
import { useTranslations } from '@/composables/useTranslations.js';
import CpIcon from '@/Components/CpIcon.vue';

const { isDark, toggle } = useTheme();
const { t } = useTranslations();
</script>

<template>
    <button
        type="button"
        class="flex items-center gap-1.5 px-2 py-1 text-xs font-medium text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 rounded-subtle transition-colors duration-150"
        :title="t('theme.toggle')"
        @click="toggle"
    >
        <CpIcon :name="isDark ? 'sun' : 'moon'" :size="16" :stroke-width="2" />
        <span>{{ isDark ? t('theme.light') : t('theme.dark') }}</span>
    </button>
</template>
