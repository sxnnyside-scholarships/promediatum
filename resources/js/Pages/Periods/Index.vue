<script setup lang="ts">
/**
 * Periods Index — Pedagogical Academic Term Manager
 *
 * Restful gradient backdrop, aesthetic table layout, MingCute icons,
 * ergonomic filtering, and automatic date-based deactivation indicators.
 */

import { Head, Link, router } from '@inertiajs/vue3';
import {
    AddCircleRegular,
    AlertRegular,
    CalendarAddRegular,
    CalendarMonthRegular,
    CheckCircleRegular,
    CloseCircleRegular,
    EyeRegular,
    RightSmallRegular,
    TimeRegular,
} from '@mingcute/vue/core-regular';
import { computed, ref } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface PeriodItem {
    id: number;
    name: string;
    slug: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
    is_past?: boolean;
    days_remaining: number;
}

interface Props {
    periods: PeriodItem[];
}

const props = withDefaults(defineProps<Props>(), {
    periods: () => [],
});

const { t } = useTranslations();

// Active filter: 'all' | 'active' | 'concluded'
const activeFilter = ref<'all' | 'active' | 'concluded'>('all');

function toggleActive(period: PeriodItem) {
    router.post(route('periods.toggle-active', period.slug));
}

function formatDate(dateStr: string) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

const filteredPeriods = computed(() => {
    if (activeFilter.value === 'active') {
        return props.periods.filter((p) => p.is_active);
    }
    if (activeFilter.value === 'concluded') {
        return props.periods.filter((p) => p.is_past || p.days_remaining === 0);
    }
    return props.periods;
});

const counts = computed(() => ({
    all: props.periods.length,
    active: props.periods.filter((p) => p.is_active).length,
    concluded: props.periods.filter((p) => p.is_past || p.days_remaining === 0).length,
}));
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('periods.title')" />

        <div class="space-y-6">
            <!-- Header zone with restful pedagogical gradient card -->
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                            <CalendarMonthRegular class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                                {{ t('periods.title') }}
                            </h1>
                            <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5 leading-relaxed">
                                {{ t('periods.subtitle') }}
                            </p>
                        </div>
                    </div>

                    <Link :href="route('periods.create')" class="shrink-0">
                        <CpButton type="button" class="inline-flex items-center gap-2 text-sm shadow-sm">
                            <AddCircleRegular class="w-4 h-4" />
                            <span>{{ t('periods.create') }}</span>
                        </CpButton>
                    </Link>
                </div>

                <!-- Ergonomic filter tabs -->
                <div v-if="periods.length > 0" class="flex items-center gap-1.5 mt-5 pt-4 border-t border-cafe-200/60 dark:border-cafe-800/60">
                    <button
                        type="button"
                        @click="activeFilter = 'all'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                        :class="activeFilter === 'all'
                            ? 'bg-cafe-800 text-white dark:bg-cafe-100 dark:text-cafe-900 shadow-sm'
                            : 'text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200/50 dark:hover:bg-surface-dark-2'"
                    >
                        {{ t('periods.filter_all') }} ({{ counts.all }})
                    </button>
                    <button
                        type="button"
                        @click="activeFilter = 'active'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                        :class="activeFilter === 'active'
                            ? 'bg-state-success text-white shadow-sm'
                            : 'text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200/50 dark:hover:bg-surface-dark-2'"
                    >
                        {{ t('periods.filter_active') }} ({{ counts.active }})
                    </button>
                    <button
                        type="button"
                        @click="activeFilter = 'concluded'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                        :class="activeFilter === 'concluded'
                            ? 'bg-cafe-600 text-white dark:bg-surface-dark-3 shadow-sm'
                            : 'text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200/50 dark:hover:bg-surface-dark-2'"
                    >
                        {{ t('periods.filter_concluded') }} ({{ counts.concluded }})
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="periods.length === 0"
                class="rounded-2xl border border-dashed border-cafe-300 dark:border-cafe-700 bg-white/50 dark:bg-surface-dark-1/50 p-12 text-center"
            >
                <div class="mx-auto w-12 h-12 rounded-2xl bg-accent-50 dark:bg-surface-dark-2 flex items-center justify-center text-accent-600 dark:text-accent-300 mb-3.5">
                    <CalendarAddRegular class="w-6 h-6" />
                </div>
                <h3 class="text-base font-serif font-bold text-cafe-800 dark:text-cafe-100 mb-1">
                    {{ t('periods.empty') }}
                </h3>
                <p class="text-xs text-cafe-500 dark:text-cafe-400 max-w-sm mx-auto mb-4 leading-relaxed">
                    {{ t('periods.subtitle') }}
                </p>
                <Link :href="route('periods.create')">
                    <CpButton type="button" class="inline-flex items-center gap-2 text-xs">
                        <AddCircleRegular class="w-3.5 h-3.5" />
                        <span>{{ t('periods.create') }}</span>
                    </CpButton>
                </Link>
            </div>

            <!-- Aesthetic Table Container -->
            <div v-else class="overflow-hidden rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-cafe-200/80 dark:border-cafe-800/90 bg-cafe-100/50 dark:bg-surface-dark-2/60 text-xs font-semibold uppercase tracking-wider text-cafe-600 dark:text-cafe-300">
                            <th class="py-3 px-5">
                                <span class="inline-flex items-center gap-1.5">
                                    <CalendarMonthRegular class="w-3.5 h-3.5 opacity-70" />
                                    {{ t('periods.name') }}
                                </span>
                            </th>
                            <th class="py-3 px-5">
                                <span class="inline-flex items-center gap-1.5">
                                    <TimeRegular class="w-3.5 h-3.5 opacity-70" />
                                    {{ t('periods.duration') }}
                                </span>
                            </th>
                            <th class="py-3 px-5 text-center">
                                {{ t('periods.days_remaining') }}
                            </th>
                            <th class="py-3 px-5 text-center">
                                <span class="inline-flex items-center justify-center gap-1.5">
                                    <CheckCircleRegular class="w-3.5 h-3.5 opacity-70" />
                                    {{ t('periods.status') }}
                                </span>
                            </th>
                            <th class="py-3 px-5 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cafe-200/60 dark:divide-cafe-800/60">
                        <tr
                            v-for="period in filteredPeriods"
                            :key="period.id"
                            class="hover:bg-cafe-50/70 dark:hover:bg-surface-dark-2/40 transition-colors group"
                            :class="{ 'bg-accent-50/30 dark:bg-accent-950/15': period.is_active }"
                        >
                            <!-- Period Name -->
                            <td class="py-3.5 px-5">
                                <Link
                                    :href="route('periods.show', period.slug)"
                                    class="font-medium text-cafe-900 dark:text-cafe-100 group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors inline-flex items-center gap-2"
                                >
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="period.is_active ? 'bg-state-success' : 'bg-cafe-300 dark:bg-cafe-600'" />
                                    <span>{{ period.name }}</span>
                                </Link>
                            </td>

                            <!-- Start & End Dates -->
                            <td class="py-3.5 px-5 text-xs text-cafe-600 dark:text-cafe-300">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs">{{ formatDate(period.start_date) }}</span>
                                    <span class="opacity-40">→</span>
                                    <span class="font-mono text-xs">{{ formatDate(period.end_date) }}</span>
                                </div>
                            </td>

                            <!-- Days remaining pill with auto-expiry indication -->
                            <td class="py-3.5 px-5 text-center">
                                <span
                                    v-if="period.is_past || period.days_remaining === 0"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-cafe-100 dark:bg-surface-dark-2 text-cafe-500 dark:text-cafe-400"
                                    :title="t('periods.auto_deactivated')"
                                >
                                    <CloseCircleRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('periods.concluded') }}</span>
                                </span>
                                <span
                                    v-else-if="period.days_remaining <= 7"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-state-warning/15 text-state-warning border border-state-warning/30"
                                >
                                    <AlertRegular class="w-3.5 h-3.5" />
                                    <span>{{ period.days_remaining }} d</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-state-success/10 text-state-success"
                                >
                                    <TimeRegular class="w-3.5 h-3.5" />
                                    <span>{{ period.days_remaining }} d</span>
                                </span>
                            </td>

                            <!-- Status with toggle button -->
                            <td class="py-3.5 px-5 text-center">
                                <button
                                    type="button"
                                    @click="toggleActive(period)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-all shadow-sm"
                                    :class="period.is_active
                                        ? 'bg-state-success text-white hover:bg-state-success/90'
                                        : 'bg-cafe-100 dark:bg-surface-dark-2 text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200 dark:hover:bg-surface-dark-3'"
                                    :title="period.is_active ? t('periods.deactivate') : t('periods.activate')"
                                >
                                    <component :is="period.is_active ? CheckCircleRegular : CloseCircleRegular" class="w-3.5 h-3.5" />
                                    <span>{{ period.is_active ? t('periods.active') : t('periods.inactive') }}</span>
                                </button>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-5 text-right">
                                <Link
                                    :href="route('periods.show', period.slug)"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-cafe-500 hover:text-accent-600 dark:text-cafe-400 dark:hover:text-accent-300 transition-colors p-1 rounded-md hover:bg-cafe-100 dark:hover:bg-surface-dark-2"
                                    :title="t('periods.view_details')"
                                >
                                    <EyeRegular class="w-4 h-4" />
                                    <RightSmallRegular class="w-4 h-4" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
