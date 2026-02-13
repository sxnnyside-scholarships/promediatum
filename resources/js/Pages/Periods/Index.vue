<script setup>
/**
 * Periods Index — Scan Mode (table listing)
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, router } from '@inertiajs/vue3';

const { t } = useTranslations();

const props = defineProps({
    periods: {
        type: Array,
        default: () => [],
    },
});

function toggleActive(period) {
    router.post(route('periods.toggle-active', period.slug));
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('periods.title')" />

        <div>
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="font-serif">{{ t('periods.title') }}</h1>
                <Link :href="route('periods.create')">
                    <CpButton type="button">
                        {{ t('periods.create') }}
                    </CpButton>
                </Link>
            </div>

            <!-- Empty state -->
            <div v-if="periods.length === 0" class="text-center py-12">
                <p class="text-sm text-cafe-500 dark:text-cafe-400">
                    {{ t('periods.empty') }}
                </p>
            </div>

            <!-- Table -->
            <div v-else class="overflow-hidden rounded-subtle border border-cafe-200 dark:border-cafe-700">
                <table class="w-full text-scan-body">
                    <thead>
                        <tr class="bg-cafe-100 dark:bg-surface-dark-2 text-left">
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200">
                                {{ t('periods.name') }}
                            </th>
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200">
                                {{ t('periods.start_date') }}
                            </th>
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200">
                                {{ t('periods.end_date') }}
                            </th>
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200 text-center">
                                {{ t('periods.days_remaining') }}
                            </th>
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200 text-center">
                                {{ t('periods.active') }}
                            </th>
                            <th class="px-scan-pad py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="period in periods"
                            :key="period.id"
                            class="border-t border-cafe-200 dark:border-cafe-700 hover:bg-cafe-50 dark:hover:bg-surface-dark-2 transition-colors duration-100"
                        >
                            <td class="px-scan-pad py-2">
                                <Link
                                    :href="route('periods.show', period.slug)"
                                    class="text-cafe-800 dark:text-cafe-100 hover:text-accent-500 dark:hover:text-accent-400 underline-offset-2 hover:underline transition-colors duration-150"
                                >
                                    {{ period.name }}
                                </Link>
                            </td>
                            <td class="px-scan-pad py-2 text-cafe-600 dark:text-cafe-300">
                                {{ formatDate(period.start_date) }}
                            </td>
                            <td class="px-scan-pad py-2 text-cafe-600 dark:text-cafe-300">
                                {{ formatDate(period.end_date) }}
                            </td>
                            <td class="px-scan-pad py-2 text-center">
                                <span
                                    :class="period.days_remaining === 0
                                        ? 'text-state-danger font-medium'
                                        : period.days_remaining <= 7
                                            ? 'text-state-warning font-medium'
                                            : 'text-cafe-600 dark:text-cafe-300'"
                                >
                                    {{ period.days_remaining }}
                                </span>
                            </td>
                            <td class="px-scan-pad py-2 text-center">
                                <button
                                    type="button"
                                    @click="toggleActive(period)"
                                    class="inline-flex items-center px-2 py-0.5 rounded-subtle text-xs font-medium transition-colors duration-150"
                                    :class="period.is_active
                                        ? 'bg-state-success/10 text-state-success'
                                        : 'bg-cafe-200 dark:bg-surface-dark-3 text-cafe-500 dark:text-cafe-400 hover:text-cafe-700 dark:hover:text-cafe-200'"
                                >
                                    {{ period.is_active ? t('periods.active') : t('periods.inactive') }}
                                </button>
                            </td>
                            <td class="px-scan-pad py-2 text-right">
                                <Link
                                    :href="route('periods.show', period.slug)"
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
