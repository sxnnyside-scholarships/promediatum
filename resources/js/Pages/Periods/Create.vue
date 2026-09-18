<script setup lang="ts">
/**
 * Periods Create — Pedagogical Academic Term Setup
 *
 * Provides quick date presets, auto-adjusting end date, real-time duration preview,
 * teacher tips with a MingCute lightbulb (foco) icon, and restful gradient card layout.
 */

import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AddCircleRegular,
    BulbRegular,
    CalendarAddRegular,
    CalendarMonthRegular,
    LeftSmallRegular,
    TimeRegular,
} from '@mingcute/vue/core-regular';
import { computed } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const { t } = useTranslations();

// Initialize with today's date formatted YYYY-MM-DD
function getTodayIso(): string {
    return new Date().toISOString().split('T')[0];
}

function addMonthsToDate(dateStr: string, months: number): string {
    const d = dateStr ? new Date(dateStr) : new Date();
    d.setMonth(d.getMonth() + months);
    return d.toISOString().split('T')[0];
}

const form = useForm({
    name: '',
    start_date: getTodayIso(),
    end_date: addMonthsToDate(getTodayIso(), 3),
});

// Ergonomic Quick Presets
function applyPreset(months: number, nameSuffix?: string) {
    if (!form.start_date) {
        form.start_date = getTodayIso();
    }
    form.end_date = addMonthsToDate(form.start_date, months);

    if (nameSuffix && !form.name) {
        const year = new Date(form.start_date).getFullYear();
        form.name = `${nameSuffix} ${year}`;
    }
}

function setStartToday() {
    form.start_date = getTodayIso();
    if (form.end_date && form.end_date < form.start_date) {
        form.end_date = addMonthsToDate(form.start_date, 3);
    }
}

// Compute estimated duration in days and weeks
const durationInfo = computed(() => {
    if (!form.start_date || !form.end_date) return null;
    const start = new Date(form.start_date);
    const end = new Date(form.end_date);
    const diffTime = end.getTime() - start.getTime();
    if (diffTime < 0) return null;

    const days = Math.round(diffTime / (1000 * 60 * 60 * 24));
    const weeks = Math.round(days / 7);
    return { days, weeks };
});

function submit() {
    form.post(route('periods.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('periods.create')" />

        <div class="space-y-6 max-w-5xl">
            <!-- Back button -->
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
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm">
                <div class="flex items-center gap-3.5">
                    <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                        <CalendarAddRegular class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                            {{ t('periods.create') }}
                        </h1>
                        <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5 leading-relaxed">
                            {{ t('periods.subtitle') }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-10 gap-6">
                <!-- Main Form (70%) -->
                <div class="lg:col-span-7 space-y-5 rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 p-6 shadow-sm">
                    <!-- Period Name -->
                    <div>
                        <CpInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            :label="t('periods.name')"
                            :error="form.errors.name"
                            required
                            autofocus
                            placeholder="Ej. Primer Trimestre 2026 / Ciclo Escolar 2026-2027"
                        />
                    </div>

                    <!-- Quick Date Presets Ergonomics -->
                    <div class="pt-2 border-t border-cafe-100 dark:border-cafe-800/60">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider text-cafe-500 dark:text-cafe-400">
                                {{ t('periods.presets_title') }}
                            </span>
                            <button
                                type="button"
                                @click="setStartToday"
                                class="text-[11px] font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400 hover:underline transition-colors"
                            >
                                {{ t('periods.preset_today') }}
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="applyPreset(3, 'Trimestre')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-cafe-100 hover:bg-accent-100 dark:bg-surface-dark-2 dark:hover:bg-surface-dark-3 text-cafe-700 dark:text-cafe-200 hover:text-accent-800 dark:hover:text-accent-300 border border-cafe-200 dark:border-cafe-700 transition-colors"
                            >
                                <CalendarMonthRegular class="w-3.5 h-3.5" />
                                <span>{{ t('periods.preset_quarter') }}</span>
                            </button>
                            <button
                                type="button"
                                @click="applyPreset(6, 'Semestre')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-cafe-100 hover:bg-accent-100 dark:bg-surface-dark-2 dark:hover:bg-surface-dark-3 text-cafe-700 dark:text-cafe-200 hover:text-accent-800 dark:hover:text-accent-300 border border-cafe-200 dark:border-cafe-700 transition-colors"
                            >
                                <CalendarMonthRegular class="w-3.5 h-3.5" />
                                <span>{{ t('periods.preset_semester') }}</span>
                            </button>
                            <button
                                type="button"
                                @click="applyPreset(10, 'Ciclo Escolar')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-cafe-100 hover:bg-accent-100 dark:bg-surface-dark-2 dark:hover:bg-surface-dark-3 text-cafe-700 dark:text-cafe-200 hover:text-accent-800 dark:hover:text-accent-300 border border-cafe-200 dark:border-cafe-700 transition-colors"
                            >
                                <CalendarMonthRegular class="w-3.5 h-3.5" />
                                <span>{{ t('periods.preset_school_year') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Date pickers side-by-side -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <div>
                            <CpInput
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                :label="t('periods.start_date')"
                                :error="form.errors.start_date"
                                required
                            />
                        </div>
                        <div>
                            <CpInput
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                :min="form.start_date"
                                :label="t('periods.end_date')"
                                :error="form.errors.end_date"
                                required
                            />
                        </div>
                    </div>

                    <!-- Dynamic Duration Preview Indicator -->
                    <div
                        v-if="durationInfo"
                        class="p-3 rounded-xl bg-cafe-50 dark:bg-surface-dark-2 border border-cafe-200/80 dark:border-cafe-800 flex items-center justify-between text-xs"
                    >
                        <div class="flex items-center gap-2 text-cafe-700 dark:text-cafe-300">
                            <TimeRegular class="w-4 h-4 text-accent-600 dark:text-accent-400 shrink-0" />
                            <span>
                                {{ t('periods.estimated_duration', { days: durationInfo.days, weeks: durationInfo.weeks }) }}
                            </span>
                        </div>
                        <span class="text-[11px] text-cafe-500 dark:text-cafe-400">
                            {{ form.start_date }} → {{ form.end_date }}
                        </span>
                    </div>

                    <!-- Submit action -->
                    <div class="pt-2">
                        <CpButton
                            type="submit"
                            :loading="form.processing"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 shadow-sm"
                        >
                            <AddCircleRegular class="w-4 h-4" />
                            <span>{{ t('periods.submit') }}</span>
                        </CpButton>
                    </div>
                </div>

                <!-- Teacher Tips Context Panel with Foco / Bulb Icon (30%) -->
                <aside class="lg:col-span-3 space-y-4">
                    <div class="rounded-2xl border border-amber-200/80 dark:border-amber-900/60 bg-gradient-to-b from-amber-50/70 to-white dark:from-surface-dark-2 dark:to-surface-dark-1 p-5 shadow-sm space-y-3.5">
                        <div class="flex items-center gap-2.5 text-amber-700 dark:text-amber-400">
                            <div class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/70">
                                <BulbRegular class="w-4 h-4" />
                            </div>
                            <h3 class="text-xs font-bold uppercase tracking-wider">
                                {{ t('periods.tips_title') }}
                            </h3>
                        </div>

                        <ul class="space-y-3 text-xs text-cafe-600 dark:text-cafe-300 leading-relaxed">
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0" />
                                <span>{{ t('periods.tip_1') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0" />
                                <span>{{ t('periods.tip_2') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0" />
                                <span>{{ t('periods.tip_auto_deactivate') }}</span>
                            </li>
                        </ul>
                    </div>
                </aside>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
