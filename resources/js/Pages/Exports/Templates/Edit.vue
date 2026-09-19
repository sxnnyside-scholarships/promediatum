<script setup lang="ts">
/**
 * Exports/Templates/Edit — Edit an existing export template.
 *
 * 70/30 grid layout with Café Pedagógico design.
 */

import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    CheckRegular,
    DocRegular,
    FileCertificateRegular,
    InformationRegular,
    Layout11Regular,
    LeftRegular,
    Settings1Regular,
} from '@mingcute/vue/core-regular';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import CpSelect from '@/Components/CpSelect.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ExportTemplate } from '@/types';

const { t } = useTranslations();

interface Props {
    template: ExportTemplate & { config: Record<string, any> };
    defaultConfig?: Record<string, any>;
}

const props = withDefaults(defineProps<Props>(), {
    defaultConfig: () => ({}),
});

const form = useForm({
    name: props.template.name,
    type: props.template.type,
    is_default: props.template.is_default,
    config: {
        included_columns: props.template.config.included_columns || [],
        column_order: props.template.config.column_order || [],
        include_logo: props.template.config.include_logo || false,
        include_header_text: props.template.config.include_header_text || '',
        include_footer_text: props.template.config.include_footer_text || '',
        include_signature_line: props.template.config.include_signature_line || false,
        date_format: props.template.config.date_format || 'Y-m-d',
        numeric_precision: props.template.config.numeric_precision ?? 2,
        orientation: props.template.config.orientation || 'portrait',
        include_attendance_summary: props.template.config.include_attendance_summary ?? true,
        include_observations_summary: props.template.config.include_observations_summary ?? true,
        include_category_breakdown: props.template.config.include_category_breakdown ?? true,
    },
});

const typeOptions = [
    { value: 'group', label: t('exports.type_group') },
    { value: 'student', label: t('exports.type_student') },
    { value: 'period', label: t('exports.type_period') },
];

const orientationOptions = [
    { value: 'portrait', label: t('templates.portrait') },
    { value: 'landscape', label: t('templates.landscape') },
];

const precisionOptions = [
    { value: '0', label: t('templates.decimals_0') },
    { value: '1', label: t('templates.decimals_1') },
    { value: '2', label: t('templates.decimals_2') },
    { value: '3', label: t('templates.decimals_3') },
    { value: '4', label: t('templates.decimals_4') },
];

function submit() {
    form.config.numeric_precision = parseInt(String(form.config.numeric_precision), 10) || 2;
    form.put(route('exports.templates.update', props.template.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('templates.edit')" />

        <div class="space-y-6 pb-12">
            <div>
                <Link
                    :href="route('exports.templates.index')"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-cafe-600 hover:text-accent-700 dark:text-cafe-400 dark:hover:text-accent-300 transition-colors"
                >
                    <LeftRegular class="w-3.5 h-3.5" />
                    <span>{{ t('templates.back') }}</span>
                </Link>
            </div>

            <div class="border-b border-cafe-200/80 dark:border-cafe-800/80 pb-5">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-2xl bg-accent-100 dark:bg-accent-950/40 text-accent-700 dark:text-accent-300">
                        <FileCertificateRegular class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="font-serif text-2xl font-bold text-cafe-900 dark:text-cafe-100 tracking-tight">
                            {{ t('templates.edit') }}: {{ template.name }}
                        </h1>
                        <p class="text-xs text-cafe-600 dark:text-cafe-400 mt-0.5">
                            {{ t('templates.subtitle_edit') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-10 gap-8 items-start">
                <!-- Form (70%) -->
                <div class="lg:col-span-7">
                    <form @submit.prevent="submit" class="space-y-6">

                        <!-- Section: General -->
                        <div class="rounded-2xl border border-cafe-200/90 dark:border-cafe-700/80 bg-white dark:bg-surface-dark-1 p-6 sm:p-7 shadow-sm space-y-5">
                            <div class="flex items-center gap-2.5 border-b border-cafe-100 dark:border-cafe-800 pb-3">
                                <div class="p-1.5 rounded-xl bg-accent-50 dark:bg-accent-950/40 text-accent-600">
                                    <Settings1Regular class="w-4 h-4" />
                                </div>
                                <h3 class="text-sm font-bold text-cafe-900 dark:text-cafe-100 uppercase tracking-wider">
                                    {{ t('templates.section_general') }}
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <CpInput
                                        id="name"
                                        v-model="form.name"
                                        :label="t('templates.name')"
                                        :error="form.errors.name"
                                        required
                                    />
                                </div>

                                <div>
                                    <CpSelect
                                        id="type"
                                        v-model="form.type"
                                        :label="t('templates.type')"
                                        :options="typeOptions"
                                        :error="form.errors.type"
                                        required
                                    />
                                </div>

                                <div class="flex items-center pt-6">
                                    <label class="relative flex items-center gap-2.5 cursor-pointer select-none">
                                        <input
                                            id="is_default"
                                            type="checkbox"
                                            v-model="form.is_default"
                                            class="w-4 h-4 rounded border-cafe-300 dark:border-cafe-600 text-accent-600 focus:ring-accent-500 dark:bg-surface-dark-2"
                                        />
                                        <span class="text-xs font-medium text-cafe-800 dark:text-cafe-200">
                                            {{ t('templates.set_default') }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Content Options -->
                        <div class="rounded-2xl border border-cafe-200/90 dark:border-cafe-700/80 bg-white dark:bg-surface-dark-1 p-6 sm:p-7 shadow-sm space-y-5">
                            <div class="flex items-center gap-2.5 border-b border-cafe-100 dark:border-cafe-800 pb-3">
                                <div class="p-1.5 rounded-xl bg-accent-50 dark:bg-accent-950/40 text-accent-600">
                                    <DocRegular class="w-4 h-4" />
                                </div>
                                <h3 class="text-sm font-bold text-cafe-900 dark:text-cafe-100 uppercase tracking-wider">
                                    {{ t('templates.section_content') }}
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <label class="p-3.5 rounded-xl border border-cafe-200/80 dark:border-cafe-800 bg-cafe-50/50 dark:bg-surface-dark-2 flex items-center gap-3 cursor-pointer hover:border-cafe-300 dark:hover:border-cafe-700 transition-colors">
                                    <input
                                        type="checkbox"
                                        v-model="form.config.include_attendance_summary"
                                        class="w-4 h-4 rounded border-cafe-300 text-accent-600 focus:ring-accent-500 dark:bg-surface-dark-3"
                                    />
                                    <span class="text-xs font-semibold text-cafe-800 dark:text-cafe-200">
                                        {{ t('templates.include_attendance') }}
                                    </span>
                                </label>

                                <label class="p-3.5 rounded-xl border border-cafe-200/80 dark:border-cafe-800 bg-cafe-50/50 dark:bg-surface-dark-2 flex items-center gap-3 cursor-pointer hover:border-cafe-300 dark:hover:border-cafe-700 transition-colors">
                                    <input
                                        type="checkbox"
                                        v-model="form.config.include_observations_summary"
                                        class="w-4 h-4 rounded border-cafe-300 text-accent-600 focus:ring-accent-500 dark:bg-surface-dark-3"
                                    />
                                    <span class="text-xs font-semibold text-cafe-800 dark:text-cafe-200">
                                        {{ t('templates.include_observations') }}
                                    </span>
                                </label>

                                <label class="p-3.5 rounded-xl border border-cafe-200/80 dark:border-cafe-800 bg-cafe-50/50 dark:bg-surface-dark-2 flex items-center gap-3 cursor-pointer hover:border-cafe-300 dark:hover:border-cafe-700 transition-colors">
                                    <input
                                        type="checkbox"
                                        v-model="form.config.include_category_breakdown"
                                        class="w-4 h-4 rounded border-cafe-300 text-accent-600 focus:ring-accent-500 dark:bg-surface-dark-3"
                                    />
                                    <span class="text-xs font-semibold text-cafe-800 dark:text-cafe-200">
                                        {{ t('templates.include_categories') }}
                                    </span>
                                </label>

                                <label class="p-3.5 rounded-xl border border-cafe-200/80 dark:border-cafe-800 bg-cafe-50/50 dark:bg-surface-dark-2 flex items-center gap-3 cursor-pointer hover:border-cafe-300 dark:hover:border-cafe-700 transition-colors">
                                    <input
                                        type="checkbox"
                                        v-model="form.config.include_signature_line"
                                        class="w-4 h-4 rounded border-cafe-300 text-accent-600 focus:ring-accent-500 dark:bg-surface-dark-3"
                                    />
                                    <span class="text-xs font-semibold text-cafe-800 dark:text-cafe-200">
                                        {{ t('templates.include_signature') }}
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Section: Formatting -->
                        <div class="rounded-2xl border border-cafe-200/90 dark:border-cafe-700/80 bg-white dark:bg-surface-dark-1 p-6 sm:p-7 shadow-sm space-y-5">
                            <div class="flex items-center gap-2.5 border-b border-cafe-100 dark:border-cafe-800 pb-3">
                                <div class="p-1.5 rounded-xl bg-accent-50 dark:bg-accent-950/40 text-accent-600">
                                    <Layout11Regular class="w-4 h-4" />
                                </div>
                                <h3 class="text-sm font-bold text-cafe-900 dark:text-cafe-100 uppercase tracking-wider">
                                    {{ t('templates.section_formatting') }}
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <CpSelect
                                        id="orientation"
                                        v-model="form.config.orientation"
                                        :label="t('templates.orientation')"
                                        :options="orientationOptions"
                                    />
                                </div>

                                <div>
                                    <CpSelect
                                        id="numeric_precision"
                                        v-model="form.config.numeric_precision"
                                        :label="t('templates.numeric_precision')"
                                        :options="precisionOptions"
                                    />
                                </div>

                                <div class="sm:col-span-2">
                                    <CpInput
                                        id="date_format"
                                        v-model="form.config.date_format"
                                        :label="t('templates.date_format')"
                                        placeholder="Y-m-d"
                                    />
                                </div>

                                <div class="sm:col-span-2">
                                    <CpInput
                                        id="header_text"
                                        v-model="form.config.include_header_text"
                                        :label="t('templates.header_text')"
                                        :placeholder="t('templates.header_text_placeholder')"
                                    />
                                </div>

                                <div class="sm:col-span-2">
                                    <CpInput
                                        id="footer_text"
                                        v-model="form.config.include_footer_text"
                                        :label="t('templates.footer_text')"
                                        :placeholder="t('templates.footer_text_placeholder')"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <Link :href="route('exports.templates.index')">
                                <button
                                    type="button"
                                    class="px-4 py-2 text-xs font-medium text-cafe-600 hover:text-cafe-900 dark:text-cafe-300 dark:hover:text-white transition-colors"
                                >
                                    {{ t('common.cancel') }}
                                </button>
                            </Link>

                            <CpButton :disabled="form.processing" class="inline-flex items-center gap-2">
                                <CheckRegular class="w-4 h-4" />
                                <span>{{ t('templates.update') }}</span>
                            </CpButton>
                        </div>
                    </form>
                </div>

                <!-- Context panel (30%) -->
                <aside class="lg:col-span-3 space-y-4">
                    <div class="rounded-2xl bg-gradient-to-br from-amber-50/80 to-cafe-50/50 dark:from-surface-dark-2 dark:to-surface-dark-1 border border-amber-200/80 dark:border-amber-900/40 p-6 space-y-4 shadow-sm">
                        <div class="flex items-center gap-2 text-accent-700 dark:text-accent-300">
                            <InformationRegular class="w-5 h-5" />
                            <h3 class="text-sm font-bold">
                                {{ t('templates.tips_title') }}
                            </h3>
                        </div>

                        <ul class="space-y-3 text-xs text-cafe-600 dark:text-cafe-400 leading-relaxed">
                            <li class="flex items-start gap-2">
                                <span class="text-accent-600 font-bold">•</span>
                                <span>{{ t('templates.tip_1') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-accent-600 font-bold">•</span>
                                <span>{{ t('templates.tip_2') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-accent-600 font-bold">•</span>
                                <span>{{ t('templates.tip_3') }}</span>
                            </li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
