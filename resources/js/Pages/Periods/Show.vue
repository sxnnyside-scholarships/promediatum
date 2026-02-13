<script setup>
/**
 * Periods Show — Read Mode (detail view)
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, router } from '@inertiajs/vue3';

const { t } = useTranslations();

const props = defineProps({
    period: Object,
});

function toggleActive() {
    router.post(route('periods.toggle-active', props.period.slug));
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="period.name" />

        <div>
            <!-- Back link -->
            <div class="mb-6">
                <Link
                    :href="route('periods.index')"
                    class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                >
                    ← {{ t('periods.back') }}
                </Link>
            </div>

            <!-- Title + status -->
            <div class="mb-8 flex items-start justify-between">
                <div>
                    <h1 class="font-serif">{{ period.name }}</h1>
                    <p class="mt-1 text-sm text-cafe-500 dark:text-cafe-400">
                        {{ t('periods.details') }}
                    </p>
                </div>
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-subtle text-xs font-medium"
                    :class="period.is_active
                        ? 'bg-state-success/10 text-state-success'
                        : 'bg-cafe-200 dark:bg-surface-dark-3 text-cafe-500 dark:text-cafe-400'"
                >
                    {{ period.is_active ? t('periods.active') : t('periods.inactive') }}
                </span>
            </div>

            <!-- Detail fields — grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                <div class="py-3 border-b border-cafe-200 dark:border-cafe-700">
                    <span class="block text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                        {{ t('periods.start_date') }}
                    </span>
                    <span class="text-sm text-cafe-800 dark:text-cafe-100">
                        {{ formatDate(period.start_date) }}
                    </span>
                </div>

                <div class="py-3 border-b border-cafe-200 dark:border-cafe-700">
                    <span class="block text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                        {{ t('periods.end_date') }}
                    </span>
                    <span class="text-sm text-cafe-800 dark:text-cafe-100">
                        {{ formatDate(period.end_date) }}
                    </span>
                </div>
            </div>

            <div class="py-3 border-b border-cafe-200 dark:border-cafe-700 mb-8">
                <span class="block text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                    {{ t('periods.days_remaining') }}
                </span>
                <span
                    class="text-sm font-medium"
                    :class="period.days_remaining === 0
                        ? 'text-state-danger'
                        : period.days_remaining <= 7
                            ? 'text-state-warning'
                            : 'text-cafe-600 dark:text-cafe-300'"
                >
                    {{ period.days_remaining }}
                </span>
            </div>

            <!-- Actions -->
            <div>
                <CpButton
                    type="button"
                    :variant="period.is_active ? 'secondary' : 'primary'"
                    @click="toggleActive"
                >
                    {{ period.is_active ? t('periods.deactivate') : t('periods.activate') }}
                </CpButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
