<script setup>
/**
 * Students Show — Read Mode (academic summary per group)
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpButton from '@/Components/CpButton.vue';
import CpSelect from '@/Components/CpSelect.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const { t } = useTranslations();

const props = defineProps({
    student: Object,
    groupSummaries: { type: Array, default: () => [] },
    observations: { type: Array, default: () => [] },
});

// Observation quick-add
const obsForm = useForm({
    student_id: '',
    group_id: '',
    type: 'performance',
    content: '',
});
const showObsForm = ref(false);

const obsTypeOptions = [
    { value: 'performance', label: t('observations.type_performance') },
    { value: 'behavior', label: t('observations.type_behavior') },
    { value: 'achievement', label: t('observations.type_achievement') },
    { value: 'followup', label: t('observations.type_followup') },
];

const groupSelectOptions = props.groupSummaries.map(gs => ({
    value: String(gs.group.id),
    label: gs.group.name,
}));

function addObservation() {
    obsForm.student_id = props.student.id;
    obsForm.post(route('observations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            obsForm.reset('group_id', 'content');
            obsForm.type = 'performance';
            showObsForm.value = false;
        },
    });
}

function toggleResolved(obs) {
    router.post(route('observations.toggle-resolved', obs.id), {}, { preserveScroll: true });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="student.full_name" />

        <div>
            <!-- Back -->
            <div class="mb-6">
                <Link
                    :href="route('students.index')"
                    class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                >
                    ← {{ t('students.back') }}
                </Link>
            </div>

            <div class="flex items-start justify-between mb-2">
                <h1 class="font-serif">{{ student.full_name }}</h1>
                <Link :href="route('exports.history', { type: 'student', student_id: student.id })">
                    <CpButton type="button" variant="ghost">{{ t('exports.title') }}</CpButton>
                </Link>
            </div>
            <p class="text-sm text-cafe-500 dark:text-cafe-400 mb-8">
                {{ student.groups?.length || 0 }} {{ t('students.groups_count') }}
            </p>

            <!-- Two-column layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Group summaries -->
                <div class="lg:col-span-2 space-y-8">
                    <div v-if="groupSummaries.length === 0" class="text-sm text-cafe-500 dark:text-cafe-400 py-4">
                        {{ t('students.no_groups') }}
                    </div>

                    <section v-for="gs in groupSummaries" :key="gs.group.id">
                        <div class="rounded-subtle border border-cafe-200 dark:border-cafe-700 overflow-hidden">
                            <!-- Group header -->
                            <div class="px-4 py-3 bg-cafe-100 dark:bg-surface-dark-2 flex items-center justify-between">
                                <div>
                                    <Link
                                        :href="route('groups.show', gs.group.slug)"
                                        class="text-sm font-medium text-cafe-800 dark:text-cafe-100 hover:text-accent-500 dark:hover:text-accent-400 underline-offset-2 hover:underline transition-colors duration-150"
                                    >
                                        {{ gs.group.name }}
                                    </Link>
                                    <span class="ml-2 text-xs text-cafe-500 dark:text-cafe-400">{{ gs.group.period?.name }}</span>
                                </div>
                                <div class="flex items-center gap-4 text-sm">
                                    <span :class="gs.summary.at_risk ? 'text-state-danger font-medium' : 'text-cafe-600 dark:text-cafe-300'">
                                        {{ gs.summary.average !== null ? gs.summary.average + '%' : '—' }}
                                    </span>
                                    <span class="text-cafe-500 dark:text-cafe-400">
                                        {{ gs.summary.attendance?.rate !== null ? gs.summary.attendance.rate + '% ' + t('attendance.rate') : '' }}
                                    </span>
                                    <span v-if="gs.summary.has_absence_alert" class="text-xs text-state-warning" :title="t('attendance.absence_alert')">●</span>
                                </div>
                            </div>

                            <!-- Category breakdown -->
                            <div v-if="gs.categories?.length" class="px-4 py-3 space-y-2">
                                <div
                                    v-for="cat in gs.categories"
                                    :key="cat.id"
                                    class="flex items-center justify-between text-sm"
                                >
                                    <div>
                                        <span class="text-cafe-700 dark:text-cafe-200">{{ cat.name }}</span>
                                        <span class="ml-1 text-xs text-cafe-400 dark:text-cafe-500">({{ cat.weight }}%)</span>
                                    </div>
                                    <span class="text-cafe-600 dark:text-cafe-300">
                                        {{ cat.average !== null ? cat.average + '%' : '—' }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="px-4 py-3 text-sm text-cafe-500 dark:text-cafe-400">
                                {{ t('grades.no_categories') }}
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right: Observations -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-medium text-cafe-700 dark:text-cafe-200 uppercase tracking-wide">
                            {{ t('observations.title') }}
                        </h2>
                        <CpButton type="button" variant="ghost" @click="showObsForm = !showObsForm">
                            {{ showObsForm ? t('common.cancel') : t('observations.add') }}
                        </CpButton>
                    </div>

                    <!-- Add observation form -->
                    <form v-if="showObsForm" @submit.prevent="addObservation" class="space-y-3 p-3 rounded-subtle border border-cafe-200 dark:border-cafe-700 bg-cafe-50 dark:bg-surface-dark-1">
                        <CpSelect id="obs_group" v-model="obsForm.group_id" :label="t('groups.name')" :options="groupSelectOptions" :placeholder="t('observations.select_group')" :error="obsForm.errors.group_id" required />
                        <CpSelect id="obs_type" v-model="obsForm.type" :label="t('observations.type')" :options="obsTypeOptions" :error="obsForm.errors.type" required />
                        <div>
                            <label for="obs_content" class="cp-label">{{ t('observations.content') }}</label>
                            <textarea id="obs_content" v-model="obsForm.content" class="cp-input min-h-[80px]" required />
                            <p v-if="obsForm.errors.content" class="cp-error">{{ obsForm.errors.content }}</p>
                        </div>
                        <CpButton :disabled="obsForm.processing">{{ t('observations.submit') }}</CpButton>
                    </form>

                    <!-- Observations list -->
                    <div v-if="observations.length === 0" class="text-sm text-cafe-500 dark:text-cafe-400">
                        {{ t('observations.empty') }}
                    </div>

                    <div v-for="obs in observations" :key="obs.id" class="p-3 rounded-subtle border border-cafe-200 dark:border-cafe-700 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
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
                                <span class="text-xs text-cafe-400 dark:text-cafe-500">{{ obs.group?.name }}</span>
                            </div>
                            <button
                                type="button"
                                @click="toggleResolved(obs)"
                                class="text-xs transition-colors duration-150"
                                :class="obs.status === 'resolved' ? 'text-state-success' : 'text-cafe-400 hover:text-cafe-600 dark:text-cafe-500 dark:hover:text-cafe-300'"
                            >
                                {{ obs.status === 'resolved' ? '✓ ' + t('observations.resolved') : t('observations.mark_resolved') }}
                            </button>
                        </div>
                        <p class="text-sm text-cafe-700 dark:text-cafe-200">{{ obs.content }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
