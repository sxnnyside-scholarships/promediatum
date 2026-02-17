<script setup>
/**
 * Exports/Templates/Create — Create a new export template.
 *
 * 70/30 grid layout. Form sections: General, Content Options, Formatting.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpInput from '@/Components/CpInput.vue';
import CpSelect from '@/Components/CpSelect.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, useForm } from '@inertiajs/vue3';

const { t } = useTranslations();

const props = defineProps({
    defaultConfig: { type: Object, default: () => ({}) },
});

const form = useForm({
    name: '',
    type: 'group',
    is_default: false,
    config: {
        included_columns: [],
        column_order: [],
        include_logo: false,
        include_header_text: '',
        include_footer_text: '',
        include_signature_line: false,
        date_format: props.defaultConfig.date_format || 'Y-m-d',
        numeric_precision: props.defaultConfig.numeric_precision ?? 2,
        orientation: 'portrait',
        include_attendance_summary: true,
        include_observations_summary: true,
        include_category_breakdown: true,
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
    { value: '0', label: '0' },
    { value: '1', label: '1' },
    { value: '2', label: '2' },
    { value: '3', label: '3' },
    { value: '4', label: '4' },
];

function submit() {
    // Ensure numeric_precision is an integer
    form.config.numeric_precision = parseInt(form.config.numeric_precision, 10) || 2;
    form.post(route('exports.templates.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('templates.create')" />

        <div>
            <div class="mb-6">
                <Link
                    :href="route('exports.templates.index')"
                    class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                >
                    ← {{ t('templates.back') }}
                </Link>
            </div>

            <h1 class="font-serif mb-6">{{ t('templates.create') }}</h1>

            <div class="grid grid-cols-1 lg:grid-cols-10 gap-8">
                <!-- Form (70%) -->
                <div class="lg:col-span-7">
                    <form @submit.prevent="submit" class="space-y-8">

                        <!-- Section: General -->
                        <fieldset class="space-y-4">
                            <legend class="text-sm font-semibold text-cafe-700 dark:text-cafe-200 border-b border-cafe-200 dark:border-cafe-700 pb-2 mb-4 w-full">
                                {{ t('templates.section_general') }}
                            </legend>

                            <CpInput
                                id="name"
                                v-model="form.name"
                                :label="t('templates.name')"
                                :error="form.errors.name"
                                required
                            />

                            <CpSelect
                                id="type"
                                v-model="form.type"
                                :label="t('templates.type')"
                                :options="typeOptions"
                                :error="form.errors.type"
                                required
                            />

                            <div class="flex items-center gap-2">
                                <input
                                    id="is_default"
                                    type="checkbox"
                                    v-model="form.is_default"
                                    class="rounded border-cafe-300 dark:border-cafe-600 text-accent-600 focus:ring-accent-500 dark:bg-surface-dark-2"
                                />
                                <label for="is_default" class="text-sm text-cafe-700 dark:text-cafe-200">
                                    {{ t('templates.set_default') }}
                                </label>
                            </div>
                        </fieldset>

                        <!-- Section: Content Options -->
                        <fieldset class="space-y-4">
                            <legend class="text-sm font-semibold text-cafe-700 dark:text-cafe-200 border-b border-cafe-200 dark:border-cafe-700 pb-2 mb-4 w-full">
                                {{ t('templates.section_content') }}
                            </legend>

                            <div class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <input
                                        id="include_attendance"
                                        type="checkbox"
                                        v-model="form.config.include_attendance_summary"
                                        class="rounded border-cafe-300 dark:border-cafe-600 text-accent-600 focus:ring-accent-500 dark:bg-surface-dark-2"
                                    />
                                    <label for="include_attendance" class="text-sm text-cafe-700 dark:text-cafe-200">
                                        {{ t('templates.include_attendance') }}
                                    </label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        id="include_observations"
                                        type="checkbox"
                                        v-model="form.config.include_observations_summary"
                                        class="rounded border-cafe-300 dark:border-cafe-600 text-accent-600 focus:ring-accent-500 dark:bg-surface-dark-2"
                                    />
                                    <label for="include_observations" class="text-sm text-cafe-700 dark:text-cafe-200">
                                        {{ t('templates.include_observations') }}
                                    </label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        id="include_categories"
                                        type="checkbox"
                                        v-model="form.config.include_category_breakdown"
                                        class="rounded border-cafe-300 dark:border-cafe-600 text-accent-600 focus:ring-accent-500 dark:bg-surface-dark-2"
                                    />
                                    <label for="include_categories" class="text-sm text-cafe-700 dark:text-cafe-200">
                                        {{ t('templates.include_categories') }}
                                    </label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        id="include_signature"
                                        type="checkbox"
                                        v-model="form.config.include_signature_line"
                                        class="rounded border-cafe-300 dark:border-cafe-600 text-accent-600 focus:ring-accent-500 dark:bg-surface-dark-2"
                                    />
                                    <label for="include_signature" class="text-sm text-cafe-700 dark:text-cafe-200">
                                        {{ t('templates.include_signature') }}
                                    </label>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Section: Formatting -->
                        <fieldset class="space-y-4">
                            <legend class="text-sm font-semibold text-cafe-700 dark:text-cafe-200 border-b border-cafe-200 dark:border-cafe-700 pb-2 mb-4 w-full">
                                {{ t('templates.section_formatting') }}
                            </legend>

                            <CpSelect
                                id="orientation"
                                v-model="form.config.orientation"
                                :label="t('templates.orientation')"
                                :options="orientationOptions"
                            />

                            <CpSelect
                                id="numeric_precision"
                                v-model="form.config.numeric_precision"
                                :label="t('templates.numeric_precision')"
                                :options="precisionOptions"
                            />

                            <CpInput
                                id="date_format"
                                v-model="form.config.date_format"
                                :label="t('templates.date_format')"
                                placeholder="Y-m-d"
                            />

                            <CpInput
                                id="header_text"
                                v-model="form.config.include_header_text"
                                :label="t('templates.header_text')"
                                :placeholder="t('templates.header_text_placeholder')"
                            />

                            <CpInput
                                id="footer_text"
                                v-model="form.config.include_footer_text"
                                :label="t('templates.footer_text')"
                                :placeholder="t('templates.footer_text_placeholder')"
                            />
                        </fieldset>

                        <CpButton :disabled="form.processing">
                            {{ t('templates.submit') }}
                        </CpButton>
                    </form>
                </div>

                <!-- Context panel (30%) -->
                <aside class="lg:col-span-3">
                    <div class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-5 space-y-3">
                        <h3 class="text-sm font-semibold text-cafe-700 dark:text-cafe-200">
                            {{ t('templates.tips_title') }}
                        </h3>
                        <ul class="space-y-2 text-sm text-cafe-500 dark:text-cafe-400">
                            <li>{{ t('templates.tip_1') }}</li>
                            <li>{{ t('templates.tip_2') }}</li>
                            <li>{{ t('templates.tip_3') }}</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
