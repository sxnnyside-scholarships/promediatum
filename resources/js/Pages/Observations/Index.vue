<script setup>
/**
 * Observations Index — Global list with filters
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpButton from '@/Components/CpButton.vue';
import CpSelect from '@/Components/CpSelect.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const { t } = useTranslations();

const props = defineProps({
    observations: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const statusFilter = ref(props.filters.status || '');
const typeFilter = ref(props.filters.type || '');
const groupFilter = ref(props.filters.group_id || '');

function applyFilters() {
    const params = {};
    if (statusFilter.value) params.status = statusFilter.value;
    if (typeFilter.value) params.type = typeFilter.value;
    if (groupFilter.value) params.group_id = groupFilter.value;

    router.get(route('observations.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}

watch([statusFilter, typeFilter, groupFilter], applyFilters);

const statusOptions = [
    { value: '', label: t('observations.all_statuses') },
    { value: 'pending', label: t('observations.pending') },
    { value: 'resolved', label: t('observations.resolved') },
];

const typeOptions = [
    { value: '', label: t('observations.all_types') },
    { value: 'performance', label: t('observations.type_performance') },
    { value: 'behavior', label: t('observations.type_behavior') },
    { value: 'achievement', label: t('observations.type_achievement') },
    { value: 'followup', label: t('observations.type_followup') },
];

const groupOptions = [
    { value: '', label: t('observations.all_groups') },
    ...props.groups.map(g => ({ value: String(g.id), label: g.name })),
];

function toggleResolved(obs) {
    router.post(route('observations.toggle-resolved', obs.id), {}, { preserveScroll: true });
}

function deleteObs(obs) {
    router.delete(route('observations.destroy', obs.id), { preserveScroll: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('observations.title')" />

        <div>
            <div class="flex items-center justify-between mb-6">
                <h1 class="font-serif">{{ t('observations.title') }}</h1>
                <Link :href="route('exports.history')">
                    <CpButton type="button" variant="ghost">{{ t('exports.title') }}</CpButton>
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-4 mb-6">
                <div class="w-48">
                    <CpSelect id="filter_status" v-model="statusFilter" :label="t('observations.status')" :options="statusOptions" />
                </div>
                <div class="w-44">
                    <CpSelect id="filter_type" v-model="typeFilter" :label="t('observations.type')" :options="typeOptions" />
                </div>
                <div class="w-48">
                    <CpSelect id="filter_group" v-model="groupFilter" :label="t('groups.name')" :options="groupOptions" />
                </div>
            </div>

            <div v-if="observations.length === 0" class="text-center py-12">
                <p class="text-sm text-cafe-500 dark:text-cafe-400">{{ t('observations.empty') }}</p>
            </div>

            <!-- Observations list -->
            <div v-else class="space-y-3">
                <div
                    v-for="obs in observations"
                    :key="obs.id"
                    class="p-4 rounded-subtle border border-cafe-200 dark:border-cafe-700 bg-white dark:bg-surface-dark-2"
                >
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-medium px-1.5 py-0.5 rounded-subtle"
                                :class="{
                                    'bg-accent-100 dark:bg-accent-900/30 text-accent-700 dark:text-accent-300': obs.type === 'achievement',
                                    'bg-state-warning/10 text-state-warning': obs.type === 'behavior',
                                    'bg-cafe-200 dark:bg-surface-dark-3 text-cafe-600 dark:text-cafe-400': obs.type === 'performance',
                                    'bg-state-info/10 text-state-info': obs.type === 'followup',
                                }"
                            >
                                {{ t('observations.type_' + obs.type) }}
                            </span>
                            <span class="text-xs text-cafe-500 dark:text-cafe-400">
                                {{ obs.student?.full_name }}
                            </span>
                            <span class="text-xs text-cafe-400 dark:text-cafe-500">·</span>
                            <span class="text-xs text-cafe-400 dark:text-cafe-500">
                                {{ obs.group?.name }}
                            </span>
                            <span class="text-xs text-cafe-400 dark:text-cafe-500">·</span>
                            <span class="text-xs text-cafe-400 dark:text-cafe-500">
                                {{ formatDate(obs.created_at) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="toggleResolved(obs)"
                                class="text-xs transition-colors duration-150"
                                :class="obs.status === 'resolved' ? 'text-state-success' : 'text-cafe-400 hover:text-cafe-600 dark:text-cafe-500 dark:hover:text-cafe-300'"
                            >
                                {{ obs.status === 'resolved' ? '✓ ' + t('observations.resolved') : t('observations.mark_resolved') }}
                            </button>
                            <button
                                type="button"
                                @click="deleteObs(obs)"
                                class="text-xs text-state-danger hover:text-state-danger/80 transition-colors duration-150"
                            >×</button>
                        </div>
                    </div>
                    <p class="text-sm text-cafe-700 dark:text-cafe-200">{{ obs.content }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
