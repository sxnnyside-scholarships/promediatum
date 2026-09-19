<script setup lang="ts">
/**
 * Exports/Templates/Index — Template listing with Café Pedagógico design.
 */

import { Head, Link, router } from '@inertiajs/vue3';
import {
    AddCircleRegular,
    CheckCircleRegular,
    Delete2Regular,
    Edit2Regular,
    FileCertificateRegular,
    LeftRegular,
} from '@mingcute/vue/core-regular';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ExportTemplate } from '@/types';

const { t } = useTranslations();

interface Props {
    templates?: ExportTemplate[];
}

withDefaults(defineProps<Props>(), {
    templates: () => [],
});

function deleteTemplate(template: ExportTemplate) {
    if (confirm(t('templates.confirm_delete'))) {
        router.delete(route('exports.templates.destroy', template.id));
    }
}

function typeBadge(type: string): string {
    const map: Record<string, string> = {
        group: t('exports.type_group'),
        student: t('exports.type_student'),
        period: t('exports.type_period'),
    };
    return map[type] || type;
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('templates.title')" />

        <div class="space-y-6 pb-12">
            <div>
                <Link
                    :href="route('exports.history')"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-cafe-600 hover:text-accent-700 dark:text-cafe-400 dark:hover:text-accent-300 transition-colors"
                >
                    <LeftRegular class="w-3.5 h-3.5" />
                    <span>{{ t('templates.back_to_exports') }}</span>
                </Link>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-cafe-200/80 dark:border-cafe-800/80 pb-5">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-2xl bg-accent-100 dark:bg-accent-950/40 text-accent-700 dark:text-accent-300">
                        <FileCertificateRegular class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="font-serif text-2xl font-bold text-cafe-900 dark:text-cafe-100 tracking-tight">
                            {{ t('templates.title') }}
                        </h1>
                        <p class="text-xs text-cafe-600 dark:text-cafe-400 mt-0.5">
                            {{ t('templates.subtitle_index') }}
                        </p>
                    </div>
                </div>

                <Link :href="route('exports.templates.create')">
                    <CpButton class="inline-flex items-center gap-2 shadow-xs text-xs">
                        <AddCircleRegular class="w-4 h-4" />
                        <span>{{ t('templates.create') }}</span>
                    </CpButton>
                </Link>
            </div>

            <!-- Empty State -->
            <div
                v-if="templates.length === 0"
                class="rounded-3xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 py-14 px-6 text-center space-y-3 shadow-xs"
            >
                <div class="w-12 h-12 mx-auto rounded-2xl bg-cafe-100 dark:bg-surface-dark-2 text-cafe-400 flex items-center justify-center">
                    <FileCertificateRegular class="w-6 h-6" />
                </div>
                <h3 class="text-sm font-bold text-cafe-800 dark:text-cafe-200">
                    {{ t('templates.empty') }}
                </h3>
                <p class="text-xs text-cafe-500 dark:text-cafe-400 max-w-sm mx-auto">
                    {{ t('templates.empty_desc') }}
                </p>
            </div>

            <!-- Table Card -->
            <div v-else class="rounded-3xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-cafe-100 dark:border-cafe-800 bg-cafe-50/50 dark:bg-surface-dark-2/50 text-[11px] font-bold text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                <th class="py-3 px-4">{{ t('templates.col_name') }}</th>
                                <th class="py-3 px-4">{{ t('templates.col_type') }}</th>
                                <th class="py-3 px-4">{{ t('templates.col_default') }}</th>
                                <th class="py-3 px-4">{{ t('templates.col_orientation') }}</th>
                                <th class="py-3 px-4">{{ t('templates.col_created') }}</th>
                                <th class="py-3 px-4 text-right">{{ t('templates.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cafe-100 dark:divide-cafe-800/60 text-xs">
                            <tr
                                v-for="tmpl in templates"
                                :key="tmpl.id"
                                class="hover:bg-cafe-50/80 dark:hover:bg-surface-dark-2/80 transition-colors"
                            >
                                <td class="py-3.5 px-4 font-semibold text-cafe-900 dark:text-cafe-100">
                                    {{ tmpl.name }}
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-cafe-100 dark:bg-surface-dark-3 text-cafe-700 dark:text-cafe-300">
                                        {{ typeBadge(tmpl.type) }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        v-if="tmpl.is_default"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-state-success/15 text-state-success"
                                    >
                                        <CheckCircleRegular class="w-3.5 h-3.5" />
                                        <span>{{ t('templates.default_yes') }}</span>
                                    </span>
                                    <span v-else class="text-cafe-400 dark:text-cafe-600">—</span>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap text-cafe-600 dark:text-cafe-300">
                                    {{ tmpl.config?.orientation === 'landscape' ? t('templates.landscape') : t('templates.portrait') }}
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap text-cafe-500 dark:text-cafe-400">
                                    {{ formatDate(tmpl.created_at) }}
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap text-right space-x-2">
                                    <Link
                                        :href="route('exports.templates.edit', tmpl.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-cafe-700 dark:text-cafe-200 hover:text-accent-700 dark:hover:text-accent-300 bg-cafe-100 hover:bg-cafe-200/80 dark:bg-surface-dark-3 dark:hover:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 transition-colors"
                                    >
                                        <Edit2Regular class="w-3.5 h-3.5" />
                                        <span>{{ t('templates.edit') }}</span>
                                    </Link>
                                    <button
                                        type="button"
                                        @click="deleteTemplate(tmpl)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-state-danger hover:bg-red-50 dark:hover:bg-red-950/40 border border-red-200/60 dark:border-red-900/40 transition-colors"
                                    >
                                        <Delete2Regular class="w-3.5 h-3.5" />
                                        <span>{{ t('templates.delete') }}</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
