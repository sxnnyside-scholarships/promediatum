<script setup>
/**
 * Students Index — Scan Mode (table listing)
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link } from '@inertiajs/vue3';

const { t } = useTranslations();

defineProps({
    students: { type: Array, default: () => [] },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('students.title')" />

        <div>
            <div class="flex items-center justify-between mb-6">
                <h1 class="font-serif">{{ t('students.title') }}</h1>
                <Link :href="route('students.create')">
                    <CpButton type="button">{{ t('students.create') }}</CpButton>
                </Link>
            </div>

            <div v-if="students.length === 0" class="text-center py-12">
                <p class="text-sm text-cafe-500 dark:text-cafe-400">{{ t('students.empty') }}</p>
            </div>

            <div v-else class="overflow-hidden rounded-subtle border border-cafe-200 dark:border-cafe-700">
                <table class="w-full text-scan-body">
                    <thead>
                        <tr class="bg-cafe-100 dark:bg-surface-dark-2 text-left">
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200">{{ t('students.name') }}</th>
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200">{{ t('students.groups') }}</th>
                            <th class="px-scan-pad py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="student in students"
                            :key="student.id"
                            class="border-t border-cafe-200 dark:border-cafe-700 hover:bg-cafe-50 dark:hover:bg-surface-dark-2 transition-colors duration-100"
                        >
                            <td class="px-scan-pad py-2">
                                <Link
                                    :href="route('students.show', student.slug)"
                                    class="text-cafe-800 dark:text-cafe-100 hover:text-accent-500 dark:hover:text-accent-400 underline-offset-2 hover:underline transition-colors duration-150"
                                >
                                    {{ student.full_name }}
                                </Link>
                            </td>
                            <td class="px-scan-pad py-2 text-cafe-600 dark:text-cafe-300">
                                <span v-if="student.groups?.length">
                                    {{ student.groups.map(g => g.name).join(', ') }}
                                </span>
                                <span v-else class="text-cafe-400 dark:text-cafe-500">—</span>
                            </td>
                            <td class="px-scan-pad py-2 text-right">
                                <Link
                                    :href="route('students.show', student.slug)"
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
