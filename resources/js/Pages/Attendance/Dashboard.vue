<script setup lang="ts">
/**
 * Attendance Dashboard — /attendance
 * Centralized attendance control for all groups in the active academic period.
 */

import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarMonthRegular,
    CheckCircleRegular,
    CheckRegular,
    GroupRegular,
    RightSmallRegular,
    SparklesRegular,
    Task2Regular,
    TimeRegular,
    User4Regular,
} from '@mingcute/vue/core-regular';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface GroupSummary {
    id: number;
    name: string;
    subject: string | null;
    educational_level: string | null;
    slug: string;
    students_count: number;
    period_name: string | null;
    has_today: boolean;
    today_stats: {
        total: number;
        present: number;
        absent: number;
        justified: number;
        rate: number | null;
    };
    overall_rate: number | null;
    last_date: string | null;
}

interface Props {
    activePeriod: {
        id: number;
        name: string;
        slug: string;
    } | null;
    groups: GroupSummary[];
    kpis: {
        total_groups: number;
        groups_completed_today: number;
        total_students: number;
        today: string;
    };
}

const props = defineProps<Props>();
const { t } = useTranslations();

function getRateColor(rate: number | null): string {
    if (rate === null) return 'text-cafe-400 dark:text-cafe-500';
    if (rate >= 90) return 'text-state-success';
    if (rate >= 75) return 'text-state-warning';
    return 'text-state-danger';
}

function getRateBg(rate: number | null): string {
    if (rate === null) return 'bg-cafe-200 dark:bg-surface-dark-3';
    if (rate >= 90) return 'bg-emerald-500';
    if (rate >= 75) return 'bg-amber-500';
    return 'bg-red-500';
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('attendance.dashboard')" />

        <div class="space-y-6">
            <!-- ═══ 1. HERO HEADER CARD ═══ -->
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                            <Task2Regular class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                                {{ t('attendance.dashboard') }}
                            </h1>
                            <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5 leading-relaxed">
                                {{ t('attendance.dashboard_subtitle') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span v-if="activePeriod" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/80 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs">
                            <CalendarMonthRegular class="w-3.5 h-3.5 text-accent-600 dark:text-accent-400" />
                            <span>{{ activePeriod.name }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/80 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs">
                            <TimeRegular class="w-3.5 h-3.5 text-accent-600 dark:text-accent-400" />
                            <span>{{ kpis.today }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- ═══ 2. METRIC KPI CARDS ═══ -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-4 shadow-xs">
                    <div class="flex items-center justify-between text-xs text-cafe-500 dark:text-cafe-400">
                        <span>{{ t('attendance.all_groups') }}</span>
                        <GroupRegular class="w-4 h-4 text-cafe-400 dark:text-cafe-500" />
                    </div>
                    <div class="mt-2 text-2xl font-bold text-cafe-900 dark:text-cafe-100">
                        {{ kpis.total_groups }}
                    </div>
                </div>

                <div class="rounded-xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-4 shadow-xs">
                    <div class="flex items-center justify-between text-xs text-cafe-500 dark:text-cafe-400">
                        <span>{{ t('attendance.completed_today') }}</span>
                        <CheckRegular class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-400">
                        {{ kpis.groups_completed_today }} <span class="text-xs text-cafe-400 dark:text-cafe-500 font-normal">/ {{ kpis.total_groups }}</span>
                    </div>
                </div>

                <div class="rounded-xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-4 shadow-xs">
                    <div class="flex items-center justify-between text-xs text-cafe-500 dark:text-cafe-400">
                        <span>{{ t('attendance.pending_today') }}</span>
                        <TimeRegular class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-400">
                        {{ kpis.total_groups - kpis.groups_completed_today }}
                    </div>
                </div>

                <div class="rounded-xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-4 shadow-xs">
                    <div class="flex items-center justify-between text-xs text-cafe-500 dark:text-cafe-400">
                        <span>{{ t('attendance.total_students') }}</span>
                        <User4Regular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                    </div>
                    <div class="mt-2 text-2xl font-bold text-cafe-900 dark:text-cafe-100">
                        {{ kpis.total_students }}
                    </div>
                </div>
            </div>

            <!-- ═══ 3. GROUPS ATTENDANCE GRID ═══ -->
            <div v-if="groups.length === 0" class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-12 text-center">
                <GroupRegular class="w-12 h-12 text-cafe-400 dark:text-cafe-600 mx-auto mb-3" />
                <h3 class="text-base font-bold text-cafe-800 dark:text-cafe-200">{{ t('attendance.no_groups_found') }}</h3>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div
                    v-for="group in groups"
                    :key="group.id"
                    class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-5 shadow-xs flex flex-col justify-between hover:border-accent-300 dark:hover:border-accent-700 transition-all duration-150 group"
                >
                    <div>
                        <!-- Header & Badges -->
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div>
                                <h3 class="font-bold text-base text-cafe-900 dark:text-cafe-50 group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors">
                                    {{ group.name }}
                                </h3>
                                <p v-if="group.subject" class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                    {{ group.subject }}
                                    <span v-if="group.educational_level" class="text-cafe-400">· {{ group.educational_level }}</span>
                                </p>
                            </div>

                            <!-- Today Status Pill -->
                            <div class="shrink-0">
                                <span
                                    v-if="group.has_today"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40"
                                >
                                    <CheckRegular class="w-3 h-3" />
                                    <span>{{ t('attendance.completed_today') }}</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/40"
                                >
                                    <TimeRegular class="w-3 h-3" />
                                    <span>{{ t('attendance.pending_today') }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Details & Rate Progress -->
                        <div class="space-y-3 my-4 pt-3 border-t border-cafe-100 dark:border-surface-dark-3 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-cafe-500 dark:text-cafe-400 flex items-center gap-1.5">
                                    <User4Regular class="w-3.5 h-3.5 text-cafe-400" />
                                    {{ t('attendance.total_students') }}:
                                </span>
                                <span class="font-semibold text-cafe-800 dark:text-cafe-200">{{ group.students_count }}</span>
                            </div>

                            <div v-if="group.has_today" class="flex items-center justify-between">
                                <span class="text-cafe-500 dark:text-cafe-400">{{ t('attendance.today_status') }}:</span>
                                <div class="flex items-center gap-2 font-medium">
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ group.today_stats.present }} P</span>
                                    <span class="text-red-600 dark:text-red-400">{{ group.today_stats.absent }} A</span>
                                    <span class="text-amber-600 dark:text-amber-400">{{ group.today_stats.justified }} J</span>
                                </div>
                            </div>

                            <!-- Overall Attendance Rate -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-cafe-500 dark:text-cafe-400">{{ t('attendance.overall_rate') }}:</span>
                                    <span class="font-bold font-mono" :class="getRateColor(group.overall_rate)">
                                        {{ group.overall_rate !== null ? group.overall_rate + '%' : '—' }}
                                    </span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-cafe-100 dark:bg-surface-dark-3 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-300"
                                        :class="getRateBg(group.overall_rate)"
                                        :style="{ width: `${group.overall_rate || 0}%` }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-cafe-100 dark:border-surface-dark-3 flex items-center gap-2">
                        <Link
                            :href="route('attendance.index', group.slug)"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-accent-600 hover:bg-accent-700 text-white shadow-xs transition-colors"
                        >
                            <span>{{ t('attendance.take_attendance') }}</span>
                            <RightSmallRegular class="w-4 h-4" />
                        </Link>
                        <Link
                            :href="`${route('attendance.index', group.slug)}?tab=matrix`"
                            class="inline-flex items-center justify-center px-3 py-2 rounded-xl text-xs font-medium text-cafe-700 dark:text-cafe-300 hover:bg-cafe-100 dark:hover:bg-surface-dark-3 border border-cafe-200 dark:border-surface-dark-3 transition-colors"
                            :title="t('attendance.view_matrix')"
                        >
                            {{ t('attendance.view_matrix') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
