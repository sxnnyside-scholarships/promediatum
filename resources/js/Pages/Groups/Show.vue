<script setup>
/**
 * Groups Show — Read Mode (detail view with students, categories, attendance)
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import CpSelect from '@/Components/CpSelect.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const { t } = useTranslations();

const props = defineProps({
    group: Object,
    students: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    totalWeight: { type: Number, default: 0 },
});

// ── Category management ──
const categoryForm = useForm({ name: '', weight: '' });
const showCategoryForm = ref(false);

function addCategory() {
    categoryForm.post(route('categories.store', props.group.slug), {
        preserveScroll: true,
        onSuccess: () => {
            categoryForm.reset();
            showCategoryForm.value = false;
        },
    });
}

function deleteCategory(id) {
    router.delete(route('categories.destroy', id), { preserveScroll: true });
}

// ── Grade entry ──
const gradeForm = useForm({
    student_id: '',
    category_id: '',
    title: '',
    score: '',
    max_score: '100',
    date: new Date().toISOString().split('T')[0],
});
const showGradeForm = ref(false);

const studentOptions = computed(() =>
    props.students.map(s => ({ value: String(s.id), label: s.full_name }))
);
const categoryOptions = computed(() =>
    props.categories.map(c => ({ value: String(c.id), label: `${c.name} (${c.weight}%)` }))
);

function addGrade() {
    gradeForm.post(route('grades.store', props.group.slug), {
        preserveScroll: true,
        onSuccess: () => {
            gradeForm.reset('student_id', 'category_id', 'title', 'score');
            gradeForm.max_score = '100';
            gradeForm.date = new Date().toISOString().split('T')[0];
            showGradeForm.value = false;
        },
    });
}

// ── Observation quick-add ──
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

function addObservation() {
    obsForm.group_id = props.group.id;
    obsForm.post(route('observations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            obsForm.reset('student_id', 'content');
            obsForm.type = 'performance';
            showObsForm.value = false;
        },
    });
}

function toggleArchive() {
    router.post(route('groups.toggle-archive', props.group.slug));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="group.name" />

        <div>
            <!-- Back -->
            <div class="mb-6">
                <Link
                    :href="route('groups.index')"
                    class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                >
                    ← {{ t('groups.back') }}
                </Link>
            </div>

            <!-- Title -->
            <div class="mb-8 flex items-start justify-between">
                <div>
                    <h1 class="font-serif">{{ group.name }}</h1>
                    <p class="mt-1 text-sm text-cafe-500 dark:text-cafe-400">
                        {{ group.subject }}
                        <span v-if="group.educational_level"> · {{ group.educational_level }}</span>
                        <span v-if="group.period"> · {{ group.period.name }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('exports.history', { type: 'group', group_id: group.id })" class="inline">
                        <CpButton type="button" variant="ghost">{{ t('exports.title') }}</CpButton>
                    </Link>
                    <Link :href="route('attendance.index', group.slug)" class="inline">
                        <CpButton type="button" variant="secondary">{{ t('attendance.title') }}</CpButton>
                    </Link>
                    <CpButton type="button" :variant="group.is_archived ? 'primary' : 'ghost'" @click="toggleArchive">
                        {{ group.is_archived ? t('groups.unarchive') : t('groups.archive') }}
                    </CpButton>
                </div>
            </div>

            <!-- Two-column layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Students list -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Students -->
                    <section>
                        <h2 class="text-sm font-medium text-cafe-700 dark:text-cafe-200 uppercase tracking-wide mb-4">
                            {{ t('groups.students_list') }} ({{ students.length }})
                        </h2>

                        <div v-if="students.length === 0" class="text-sm text-cafe-500 dark:text-cafe-400 py-4">
                            {{ t('groups.no_students') }}
                        </div>

                        <div v-else class="overflow-hidden rounded-subtle border border-cafe-200 dark:border-cafe-700">
                            <table class="w-full text-scan-body">
                                <thead>
                                    <tr class="bg-cafe-100 dark:bg-surface-dark-2 text-left">
                                        <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200">{{ t('students.name') }}</th>
                                        <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200 text-center">{{ t('grades.average') }}</th>
                                        <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200 text-center">{{ t('attendance.rate') }}</th>
                                        <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200 text-center">{{ t('students.risk') }}</th>
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
                                            <span
                                                v-if="student.summary?.has_absence_alert"
                                                class="ml-1.5 text-xs text-state-warning"
                                                :title="t('attendance.absence_alert')"
                                            >●</span>
                                        </td>
                                        <td class="px-scan-pad py-2 text-center">
                                            <span
                                                v-if="student.summary?.average !== null"
                                                :class="student.summary?.at_risk ? 'text-state-danger font-medium' : 'text-cafe-600 dark:text-cafe-300'"
                                            >
                                                {{ student.summary.average }}%
                                            </span>
                                            <span v-else class="text-cafe-400 dark:text-cafe-500">—</span>
                                        </td>
                                        <td class="px-scan-pad py-2 text-center text-cafe-600 dark:text-cafe-300">
                                            {{ student.summary?.attendance?.rate !== null ? student.summary.attendance.rate + '%' : '—' }}
                                        </td>
                                        <td class="px-scan-pad py-2 text-center">
                                            <span
                                                v-if="student.summary?.at_risk"
                                                class="text-xs text-state-danger font-medium"
                                            >{{ t('students.at_risk') }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Grade entry -->
                    <section>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-medium text-cafe-700 dark:text-cafe-200 uppercase tracking-wide">
                                {{ t('grades.title') }}
                            </h2>
                            <CpButton type="button" variant="ghost" @click="showGradeForm = !showGradeForm">
                                {{ showGradeForm ? t('common.cancel') : t('grades.add') }}
                            </CpButton>
                        </div>

                        <form v-if="showGradeForm" @submit.prevent="addGrade" class="space-y-4 p-4 rounded-subtle border border-cafe-200 dark:border-cafe-700 bg-cafe-50 dark:bg-surface-dark-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <CpSelect id="grade_student" v-model="gradeForm.student_id" :label="t('students.name')" :options="studentOptions" :placeholder="t('grades.select_student')" :error="gradeForm.errors.student_id" required />
                                <CpSelect id="grade_category" v-model="gradeForm.category_id" :label="t('grades.category')" :options="categoryOptions" :placeholder="t('grades.select_category')" :error="gradeForm.errors.category_id" required />
                            </div>
                            <CpInput id="grade_title" v-model="gradeForm.title" :label="t('grades.title_label')" :error="gradeForm.errors.title" required />
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <CpInput id="grade_score" v-model="gradeForm.score" :label="t('grades.score')" type="number" :error="gradeForm.errors.score" required />
                                <CpInput id="grade_max" v-model="gradeForm.max_score" :label="t('grades.max_score')" type="number" :error="gradeForm.errors.max_score" required />
                                <CpInput id="grade_date" v-model="gradeForm.date" :label="t('grades.date')" type="date" :error="gradeForm.errors.date" required />
                            </div>
                            <CpButton :disabled="gradeForm.processing">{{ t('grades.submit') }}</CpButton>
                        </form>
                    </section>

                    <!-- Observation quick-add -->
                    <section>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-medium text-cafe-700 dark:text-cafe-200 uppercase tracking-wide">
                                {{ t('observations.title') }}
                            </h2>
                            <CpButton type="button" variant="ghost" @click="showObsForm = !showObsForm">
                                {{ showObsForm ? t('common.cancel') : t('observations.add') }}
                            </CpButton>
                        </div>

                        <form v-if="showObsForm" @submit.prevent="addObservation" class="space-y-4 p-4 rounded-subtle border border-cafe-200 dark:border-cafe-700 bg-cafe-50 dark:bg-surface-dark-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <CpSelect id="obs_student" v-model="obsForm.student_id" :label="t('students.name')" :options="studentOptions" :placeholder="t('observations.select_student')" :error="obsForm.errors.student_id" required />
                                <CpSelect id="obs_type" v-model="obsForm.type" :label="t('observations.type')" :options="obsTypeOptions" :error="obsForm.errors.type" required />
                            </div>
                            <div>
                                <label for="obs_content" class="cp-label">{{ t('observations.content') }}</label>
                                <textarea
                                    id="obs_content"
                                    v-model="obsForm.content"
                                    class="cp-input min-h-[80px]"
                                    required
                                />
                                <p v-if="obsForm.errors.content" class="cp-error">{{ obsForm.errors.content }}</p>
                            </div>
                            <CpButton :disabled="obsForm.processing">{{ t('observations.submit') }}</CpButton>
                        </form>
                    </section>
                </div>

                <!-- Right: Categories & summary -->
                <div class="space-y-8">
                    <!-- Grade Categories -->
                    <section>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-medium text-cafe-700 dark:text-cafe-200 uppercase tracking-wide">
                                {{ t('grades.categories') }}
                            </h2>
                            <CpButton type="button" variant="ghost" @click="showCategoryForm = !showCategoryForm">
                                {{ showCategoryForm ? t('common.cancel') : t('common.add') }}
                            </CpButton>
                        </div>

                        <div class="space-y-2 mb-4">
                            <div
                                v-for="cat in categories"
                                :key="cat.id"
                                class="flex items-center justify-between px-3 py-2 rounded-subtle border border-cafe-200 dark:border-cafe-700 bg-white dark:bg-surface-dark-2"
                            >
                                <div>
                                    <span class="text-sm text-cafe-800 dark:text-cafe-100">{{ cat.name }}</span>
                                    <span class="ml-2 text-xs text-cafe-500 dark:text-cafe-400">{{ cat.weight }}%</span>
                                </div>
                                <button
                                    type="button"
                                    @click="deleteCategory(cat.id)"
                                    class="text-xs text-state-danger hover:text-state-danger/80 transition-colors duration-150"
                                >×</button>
                            </div>
                        </div>

                        <!-- Weight total -->
                        <div class="text-xs text-cafe-500 dark:text-cafe-400 mb-3">
                            {{ t('grades.total_weight') }}: {{ totalWeight }}%
                            <span v-if="totalWeight < 100" class="text-state-warning ml-1">({{ t('grades.incomplete') }})</span>
                            <span v-else-if="totalWeight === 100" class="text-state-success ml-1">✓</span>
                        </div>

                        <!-- Add category form -->
                        <form v-if="showCategoryForm" @submit.prevent="addCategory" class="space-y-3 p-3 rounded-subtle border border-cafe-200 dark:border-cafe-700 bg-cafe-50 dark:bg-surface-dark-1">
                            <CpInput id="cat_name" v-model="categoryForm.name" :label="t('grades.category_name')" :error="categoryForm.errors.name" required />
                            <CpInput id="cat_weight" v-model="categoryForm.weight" :label="t('grades.weight')" type="number" :error="categoryForm.errors.weight" required />
                            <CpButton :disabled="categoryForm.processing">{{ t('common.add') }}</CpButton>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
