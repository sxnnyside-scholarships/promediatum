<script setup lang="ts">
/**
 * Groups Create — Pedagogical Academic Group Setup
 *
 * Designed with the unified Café Pedagógico Design System:
 * restful header card, icon badges, teacher tips panel with BulbRegular,
 * rounded-2xl geometry, and ergonomic controls.
 */

import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AddCircleRegular,
    Book2Regular,
    BulbRegular,
    CalendarMonthRegular,
    GroupRegular,
    LeftSmallRegular,
} from '@mingcute/vue/core-regular';
import { computed } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import CpSelect from '@/Components/CpSelect.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface PeriodItem {
    id: number;
    name: string;
    is_active?: boolean;
}

const props = defineProps<{
    periods?: PeriodItem[];
}>();

const { t } = useTranslations();

const form = useForm({
    name: '',
    subject: '',
    educational_level: '',
    period_id: '',
});

const periodOptions = computed(() =>
    (props.periods || []).map((p) => ({
        value: String(p.id),
        label: `${p.name} ${p.is_active ? `(${t('groups.active')})` : ''}`,
    })),
);

function submit() {
    form.post(route('groups.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('groups.create')" />

        <div class="space-y-6 max-w-5xl">
            <!-- Back button -->
            <div>
                <Link
                    :href="route('groups.index')"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-cafe-600 dark:text-cafe-400 hover:text-cafe-900 dark:hover:text-cafe-100 transition-colors"
                >
                    <LeftSmallRegular class="w-4 h-4" />
                    <span>{{ t('groups.back') }}</span>
                </Link>
            </div>

            <!-- Header card with restful pedagogical gradient -->
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm">
                <div class="flex items-center gap-3.5">
                    <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                        <GroupRegular class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                            {{ t('groups.create') }}
                        </h1>
                        <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5 leading-relaxed">
                            {{ t('groups.subtitle') }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-10 gap-6">
                <!-- Main Form (70%) -->
                <div class="lg:col-span-7 space-y-5 rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 p-6 shadow-sm">
                    <div class="flex items-center gap-2 border-b border-cafe-100 dark:border-cafe-800 pb-3">
                        <Book2Regular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                        <h2 class="text-xs font-bold uppercase tracking-wider text-cafe-700 dark:text-cafe-200">
                            {{ t('groups.name') }}
                        </h2>
                    </div>

                    <CpInput
                        id="name"
                        v-model="form.name"
                        :label="t('groups.name')"
                        :error="form.errors.name"
                        placeholder="Ej. Matemáticas 3° B / Taller de Redacción"
                        required
                        autofocus
                    />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <CpInput
                            id="subject"
                            v-model="form.subject"
                            :label="t('groups.subject')"
                            :error="form.errors.subject"
                            placeholder="Ej. Matemáticas / Lengua"
                        />

                        <CpInput
                            id="educational_level"
                            v-model="form.educational_level"
                            :label="t('groups.educational_level')"
                            :error="form.errors.educational_level"
                            placeholder="Ej. Secundaria / Bachillerato"
                        />
                    </div>

                    <div class="pt-1">
                        <CpSelect
                            id="period_id"
                            v-model="form.period_id"
                            :label="t('groups.period')"
                            :options="periodOptions"
                            :placeholder="t('groups.select_period')"
                            :error="form.errors.period_id"
                            required
                        />
                    </div>

                    <div class="pt-3 border-t border-cafe-100 dark:border-cafe-800">
                        <CpButton
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 text-sm shadow-sm"
                        >
                            <AddCircleRegular class="w-4 h-4" />
                            <span>{{ t('groups.submit') }}</span>
                        </CpButton>
                    </div>
                </div>

                <!-- Teacher Tips Context Panel with BulbRegular (30%) -->
                <aside class="lg:col-span-3 space-y-4">
                    <div class="rounded-2xl border border-amber-200/80 dark:border-amber-900/60 bg-gradient-to-b from-amber-50/70 to-white dark:from-surface-dark-2 dark:to-surface-dark-1 p-5 shadow-sm space-y-3.5">
                        <div class="flex items-center gap-2.5 text-amber-700 dark:text-amber-400">
                            <div class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/70">
                                <BulbRegular class="w-4 h-4" />
                            </div>
                            <h3 class="text-xs font-bold uppercase tracking-wider">
                                {{ t('groups.tips_title') }}
                            </h3>
                        </div>

                        <ul class="space-y-3 text-xs text-cafe-600 dark:text-cafe-300 leading-relaxed">
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0" />
                                <span>{{ t('groups.tip_1') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0" />
                                <span>{{ t('groups.tip_2') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0" />
                                <span>{{ t('groups.tip_3') }}</span>
                            </li>
                        </ul>
                    </div>
                </aside>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
