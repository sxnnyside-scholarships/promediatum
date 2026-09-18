<script setup lang="ts">
/**
 * Periods Show — Pedagogical Academic Term Details
 *
 * Visual metric cards, status indicators with automatic deactivation notice,
 * MingCute icons, and export navigation.
 */

import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarMonthRegular,
    CheckCircleRegular,
    CloseCircleRegular,
    DownloadRegular,
    LeftSmallRegular,
    TimeRegular,
} from '@mingcute/vue/core-regular';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface PeriodData {
    id: number;
    name: string;
    slug: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
    is_past?: boolean;
    days_remaining: number;
}

const props = defineProps<{
    period: PeriodData;
}>();

const { t } = useTranslations();

function toggleActive() {
    router.post(route('periods.toggle-active', props.period.slug));
}

function formatDate(dateStr: string) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="period.name" />

        <div class="space-y-6 max-w-5xl">
            <!-- Back navigation -->
            <div>
                <Link
                    :href="route('periods.index')"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-cafe-600 dark:text-cafe-400 hover:text-cafe-900 dark:hover:text-cafe-100 transition-colors"
                >
                    <LeftSmallRegular class="w-4 h-4" />
                    <span>{{ t('periods.back') }}</span>
                </Link>
            </div>

            <!-- Header card with restful pedagogical gradient -->
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-6 sm:p-7 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="p-3 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                            <CalendarMonthRegular class="w-7 h-7" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                                {{ period.name }}
                            </h1>
                            <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5">
                                {{ t('periods.details') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <Link :href="route('exports.history', { type: 'period', period_id: period.id })">
                            <CpButton type="button" variant="ghost" class="inline-flex items-center gap-1.5 text-xs">
                                <DownloadRegular class="w-4 h-4" />
                                <span>{{ t('exports.title') }}</span>
                            </CpButton>
                        </Link>
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm"
                            :class="period.is_active
                                ? 'bg-state-success text-white'
                                : 'bg-cafe-200 dark:bg-surface-dark-3 text-cafe-600 dark:text-cafe-300'"
                        >
                            <component :is="period.is_active ? CheckCircleRegular : CloseCircleRegular" class="w-3.5 h-3.5" />
                            <span>{{ period.is_active ? t('periods.active') : t('periods.inactive') }}</span>
                        </span>
                    </div>
                </div>

                <!-- Auto-deactivation alert banner if past -->
                <div
                    v-if="period.is_past || period.days_remaining === 0"
                    class="mt-5 p-3 rounded-xl bg-cafe-100/80 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 flex items-center gap-2.5 text-xs text-cafe-600 dark:text-cafe-300"
                >
                    <TimeRegular class="w-4 h-4 text-cafe-500 shrink-0" />
                    <span>{{ t('periods.auto_deactivated') }}</span>
                </div>
            </div>

            <!-- Detail Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="p-5 rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 shadow-sm space-y-1">
                    <span class="block text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                        {{ t('periods.start_date') }}
                    </span>
                    <span class="text-base font-semibold text-cafe-900 dark:text-cafe-100 font-sans">
                        {{ formatDate(period.start_date) }}
                    </span>
                </div>

                <div class="p-5 rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 shadow-sm space-y-1">
                    <span class="block text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                        {{ t('periods.end_date') }}
                    </span>
                    <span class="text-base font-semibold text-cafe-900 dark:text-cafe-100 font-sans">
                        {{ formatDate(period.end_date) }}
                    </span>
                </div>

                <div class="p-5 rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 shadow-sm space-y-1">
                    <span class="block text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                        {{ t('periods.days_remaining') }}
                    </span>
                    <div class="flex items-center gap-2">
                        <span
                            class="text-base font-bold font-sans"
                            :class="period.days_remaining === 0
                                ? 'text-cafe-400 dark:text-cafe-500'
                                : period.days_remaining <= 7
                                    ? 'text-state-warning'
                                    : 'text-state-success'"
                        >
                            {{ period.days_remaining === 0 ? t('periods.concluded') : `${period.days_remaining} días` }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="pt-2">
                <CpButton
                    type="button"
                    :variant="period.is_active ? 'secondary' : 'primary'"
                    class="inline-flex items-center gap-2 shadow-sm"
                    @click="toggleActive"
                >
                    <component :is="period.is_active ? CloseCircleRegular : CheckCircleRegular" class="w-4 h-4" />
                    <span>{{ period.is_active ? t('periods.deactivate') : t('periods.activate') }}</span>
                </CpButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
