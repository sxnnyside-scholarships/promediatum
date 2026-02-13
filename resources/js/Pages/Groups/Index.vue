<script setup>
/**
 * Groups Index — Scan Mode (table listing)
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link } from '@inertiajs/vue3';

const { t } = useTranslations();

defineProps({
    groups: { type: Array, default: () => [] },
    periods: { type: Array, default: () => [] },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('groups.title')" />

        <div>
            <div class="flex items-center justify-between mb-6">
                <h1 class="font-serif">{{ t('groups.title') }}</h1>
                <Link :href="route('groups.create')">
                    <CpButton type="button">{{ t('groups.create') }}</CpButton>
                </Link>
            </div>

            <div v-if="groups.length === 0" class="text-center py-12">
                <p class="text-sm text-cafe-500 dark:text-cafe-400">{{ t('groups.empty') }}</p>
            </div>

            <div v-else class="overflow-hidden rounded-subtle border border-cafe-200 dark:border-cafe-700">
                <table class="w-full text-scan-body">
                    <thead>
                        <tr class="bg-cafe-100 dark:bg-surface-dark-2 text-left">
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200">{{ t('groups.name') }}</th>
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200">{{ t('groups.subject') }}</th>
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200">{{ t('groups.period') }}</th>
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200 text-center">{{ t('groups.students_count') }}</th>
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200 text-center">{{ t('groups.status') }}</th>
                            <th class="px-scan-pad py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="group in groups"
                            :key="group.id"
                            class="border-t border-cafe-200 dark:border-cafe-700 hover:bg-cafe-50 dark:hover:bg-surface-dark-2 transition-colors duration-100"
                        >
                            <td class="px-scan-pad py-2">
                                <Link
                                    :href="route('groups.show', group.slug)"
                                    class="text-cafe-800 dark:text-cafe-100 hover:text-accent-500 dark:hover:text-accent-400 underline-offset-2 hover:underline transition-colors duration-150"
                                >
                                    {{ group.name }}
                                </Link>
                            </td>
                            <td class="px-scan-pad py-2 text-cafe-600 dark:text-cafe-300">
                                {{ group.subject || '—' }}
                            </td>
                            <td class="px-scan-pad py-2 text-cafe-600 dark:text-cafe-300">
                                {{ group.period?.name || '—' }}
                            </td>
                            <td class="px-scan-pad py-2 text-center text-cafe-600 dark:text-cafe-300">
                                {{ group.students_count }}
                            </td>
                            <td class="px-scan-pad py-2 text-center">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-subtle text-xs font-medium"
                                    :class="group.is_archived
                                        ? 'bg-cafe-200 dark:bg-surface-dark-3 text-cafe-500 dark:text-cafe-400'
                                        : 'bg-state-success/10 text-state-success'"
                                >
                                    {{ group.is_archived ? t('groups.archived') : t('groups.active') }}
                                </span>
                            </td>
                            <td class="px-scan-pad py-2 text-right">
                                <Link
                                    :href="route('groups.show', group.slug)"
                                    class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 transition-colors duration-150"
                                >
                                    →
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
