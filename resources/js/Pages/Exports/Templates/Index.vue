<script setup>
/**
 * Exports/Templates/Index — Template listing in SCAN-mode table.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, router } from '@inertiajs/vue3';

const { t } = useTranslations();

const props = defineProps({
    templates: { type: Array, default: () => [] },
});

function deleteTemplate(template) {
    if (confirm(t('templates.confirm_delete'))) {
        router.delete(route('exports.templates.destroy', template.id));
    }
}

function typeBadge(type) {
    const map = {
        group: t('exports.type_group'),
        student: t('exports.type_student'),
        period: t('exports.type_period'),
    };
    return map[type] || type;
}

function formatDate(dateStr) {
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

        <div>
            <div class="flex items-center justify-between mb-6">
                <h1 class="font-serif">{{ t('templates.title') }}</h1>
                <Link :href="route('exports.templates.create')">
                    <CpButton>{{ t('templates.create') }}</CpButton>
                </Link>
            </div>

            <div class="mb-4">
                <Link
                    :href="route('exports.history')"
                    class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                >
                    ← {{ t('templates.back_to_exports') }}
                </Link>
            </div>

            <div v-if="templates.length === 0" class="text-center py-12">
                <p class="text-sm text-cafe-500 dark:text-cafe-400">{{ t('templates.empty') }}</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-cafe-200 dark:border-cafe-700">
                            <th class="text-left py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('templates.col_name') }}
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('templates.col_type') }}
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('templates.col_default') }}
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('templates.col_orientation') }}
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('templates.col_created') }}
                            </th>
                            <th class="text-right py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('templates.col_actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="tmpl in templates"
                            :key="tmpl.id"
                            class="border-b border-cafe-100 dark:border-cafe-800 hover:bg-cafe-50 dark:hover:bg-surface-dark-3 transition-colors duration-100"
                        >
                            <td class="py-2.5 px-3 font-medium text-cafe-800 dark:text-cafe-100">
                                {{ tmpl.name }}
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="text-xs font-medium px-1.5 py-0.5 rounded-subtle bg-cafe-200 dark:bg-surface-dark-3 text-cafe-600 dark:text-cafe-400">
                                    {{ typeBadge(tmpl.type) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3">
                                <span v-if="tmpl.is_default" class="text-xs font-medium px-1.5 py-0.5 rounded-subtle bg-state-success/10 text-state-success">
                                    {{ t('templates.default_yes') }}
                                </span>
                                <span v-else class="text-xs text-cafe-400 dark:text-cafe-600">—</span>
                            </td>
                            <td class="py-2.5 px-3 text-xs text-cafe-500 dark:text-cafe-400">
                                {{ tmpl.config?.orientation === 'landscape' ? t('templates.landscape') : t('templates.portrait') }}
                            </td>
                            <td class="py-2.5 px-3 text-xs text-cafe-500 dark:text-cafe-400">
                                {{ formatDate(tmpl.created_at) }}
                            </td>
                            <td class="py-2.5 px-3 text-right space-x-3">
                                <Link
                                    :href="route('exports.templates.edit', tmpl.id)"
                                    class="text-xs text-accent-600 dark:text-accent-400 hover:text-accent-800 dark:hover:text-accent-200 transition-colors duration-150"
                                >
                                    {{ t('templates.edit') }}
                                </Link>
                                <button
                                    @click="deleteTemplate(tmpl)"
                                    class="text-xs text-state-danger hover:text-red-700 dark:hover:text-red-300 transition-colors duration-150"
                                >
                                    {{ t('templates.delete') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
