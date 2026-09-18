<script setup lang="ts">
/**
 * Groups Show — Pedagogical Academic Group Workspace
 *
 * Integrated student roster (Nombre Apellido), bulk enrollment with multi-select,
 * quick student detachment (baja), grading rubric & weights visualization,
 * inline student grading, and pedagogical observation entry.
 */

import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AddCircleRegular,
    AlertRegular,
    Book2Regular,
    CalendarMonthRegular,
    CheckCircleRegular,
    ClipboardRegular,
    CloseCircleRegular,
    Delete2Regular,
    DownloadRegular,
    LeftSmallRegular,
    RightSmallRegular,
    Search2Regular,
    TransferRegular,
    User4Regular,
    UserAddRegular,
    UserRemoveRegular,
} from '@mingcute/vue/core-regular';
import { computed, ref } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import CpSelect from '@/Components/CpSelect.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface PeriodInfo {
    id: number;
    name: string;
    slug?: string;
    is_active?: boolean;
}

interface GradeCategoryItem {
    id: number;
    name: string;
    weight: number;
}

interface StudentSummary {
    average: number | null;
    attendance: {
        total: number;
        present: number;
        absent: number;
        justified: number;
        rate: number | null;
    };
    absence_streak: number;
    has_absence_alert: boolean;
    at_risk: boolean;
}

interface EnrolledStudent {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    slug: string;
    summary?: StudentSummary;
}

interface AvailableStudent {
    id: number;
    first_name: string;
    last_name: string;
    slug: string;
}

interface GroupDetail {
    id: number;
    name: string;
    slug: string;
    subject: string | null;
    educational_level: string | null;
    period_id: number;
    period?: PeriodInfo | null;
    is_archived: boolean;
}

interface Props {
    group: GroupDetail;
    students: EnrolledStudent[];
    categories: GradeCategoryItem[];
    totalWeight: number;
    availableStudents: AvailableStudent[];
    allPeriods: PeriodInfo[];
}

const props = withDefaults(defineProps<Props>(), {
    students: () => [],
    categories: () => [],
    totalWeight: 0,
    availableStudents: () => [],
    allPeriods: () => [],
});

const { t } = useTranslations();

// Active workspace tab: 'roster' | 'rubric'
const currentTab = ref<'roster' | 'rubric'>('roster');

// ── Move Period Modal ──
const showMoveModal = ref(false);
const moveForm = useForm({
    period_id: String(props.group.period_id),
});

function submitMovePeriod() {
    moveForm.patch(route('groups.move-period', props.group.slug), {
        preserveScroll: true,
        onSuccess: () => {
            showMoveModal.value = false;
        },
    });
}

// ── Bulk Student Enrollment Modal ──
const showBulkEnrollModal = ref(false);
const bulkSearchQuery = ref('');
const selectedStudentIds = ref<number[]>([]);
const bulkForm = useForm({
    student_ids: [] as number[],
});

const filteredAvailableStudents = computed(() => {
    if (!bulkSearchQuery.value.trim()) {
        return props.availableStudents;
    }
    const q = bulkSearchQuery.value.toLowerCase().trim();
    return props.availableStudents.filter(
        (s) => s.first_name.toLowerCase().includes(q) || s.last_name.toLowerCase().includes(q),
    );
});

function toggleSelectStudent(id: number) {
    const idx = selectedStudentIds.value.indexOf(id);
    if (idx >= 0) {
        selectedStudentIds.value.splice(idx, 1);
    } else {
        selectedStudentIds.value.push(id);
    }
}

function selectAllAvailable() {
    selectedStudentIds.value = filteredAvailableStudents.value.map((s) => s.id);
}

function deselectAllAvailable() {
    selectedStudentIds.value = [];
}

function submitBulkEnroll() {
    if (selectedStudentIds.value.length === 0) return;

    bulkForm.student_ids = [...selectedStudentIds.value];
    bulkForm.post(route('groups.bulk-add-students', props.group.slug), {
        preserveScroll: true,
        onSuccess: () => {
            selectedStudentIds.value = [];
            showBulkEnrollModal.value = false;
        },
    });
}

// ── Remove / Unenroll Student ──
function unenrollStudent(student: EnrolledStudent) {
    if (confirm(`${t('groups.confirm_unenroll')} (${student.first_name} ${student.last_name})`)) {
        router.delete(route('groups.remove-student', [props.group.slug, student.id]), {
            preserveScroll: true,
        });
    }
}

// ── Grade Entry Modal ──
const showGradeModal = ref(false);
const gradeForm = useForm({
    student_id: '',
    category_id: '',
    title: '',
    score: '',
    max_score: '100',
    date: new Date().toISOString().split('T')[0],
});

function openGradeModalForStudent(studentId?: number) {
    if (studentId) {
        gradeForm.student_id = String(studentId);
    }
    showGradeModal.value = true;
}

function submitGrade() {
    gradeForm.post(route('grades.store', props.group.slug), {
        preserveScroll: true,
        onSuccess: () => {
            gradeForm.reset('student_id', 'category_id', 'title', 'score');
            gradeForm.max_score = '100';
            gradeForm.date = new Date().toISOString().split('T')[0];
            showGradeModal.value = false;
        },
    });
}

// ── Observation Entry Modal ──
const showObsModal = ref(false);
const obsForm = useForm({
    student_id: '',
    group_id: props.group.id,
    type: 'performance',
    content: '',
});

function openObsModalForStudent(studentId?: number) {
    if (studentId) {
        obsForm.student_id = String(studentId);
    }
    showObsModal.value = true;
}

const obsTypeOptions = [
    { value: 'performance', label: t('observations.type_performance') },
    { value: 'behavior', label: t('observations.type_behavior') },
    { value: 'achievement', label: t('observations.type_achievement') },
    { value: 'followup', label: t('observations.type_followup') },
];

function submitObservation() {
    obsForm.group_id = props.group.id;
    obsForm.post(route('observations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            obsForm.reset('student_id', 'content');
            obsForm.type = 'performance';
            showObsModal.value = false;
        },
    });
}

// ── Category / Rubric Management ──
const showCategoryModal = ref(false);
const categoryForm = useForm({
    name: '',
    weight: '',
});

function submitCategory() {
    categoryForm.post(route('categories.store', props.group.slug), {
        preserveScroll: true,
        onSuccess: () => {
            categoryForm.reset();
            showCategoryModal.value = false;
        },
    });
}

function deleteCategory(id: number) {
    router.delete(route('categories.destroy', id), { preserveScroll: true });
}

function toggleArchive() {
    router.post(route('groups.toggle-archive', props.group.slug));
}

// Options for selects
const studentOptions = computed(() =>
    props.students.map((s) => ({
        value: String(s.id),
        label: `${s.first_name} ${s.last_name}`,
    })),
);

const categoryOptions = computed(() =>
    props.categories.map((c) => ({
        value: String(c.id),
        label: `${c.name} (${c.weight}%)`,
    })),
);

const allPeriodOptions = computed(() =>
    props.allPeriods.map((p) => ({
        value: String(p.id),
        label: `${p.name} ${p.is_active ? `(${t('groups.active')})` : ''}`,
    })),
);

// Group metrics calculations
const averageGroupScore = computed(() => {
    const scores = props.students
        .map((s) => s.summary?.average)
        .filter((a): a is number => a !== null && a !== undefined);
    if (scores.length === 0) return null;
    return Math.round(scores.reduce((a, b) => a + b, 0) / scores.length);
});

const atRiskCount = computed(() => props.students.filter((s) => s.summary?.at_risk).length);
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="group.name" />

        <div class="space-y-6">
            <!-- Back navigation -->
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
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                    <div class="flex items-start gap-3.5">
                        <div class="p-3 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                            <Book2Regular class="w-7 h-7" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                                    {{ group.name }}
                                </h1>
                                <span
                                    v-if="group.is_archived"
                                    class="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-cafe-200 dark:bg-surface-dark-3 text-cafe-600 dark:text-cafe-400"
                                >
                                    {{ t('groups.archived') }}
                                </span>
                            </div>

                            <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-1 flex items-center gap-2 flex-wrap">
                                <span v-if="group.subject" class="font-medium text-cafe-800 dark:text-cafe-100">{{ group.subject }}</span>
                                <span v-if="group.educational_level">· {{ group.educational_level }}</span>
                                <span v-if="group.period" class="inline-flex items-center gap-1 text-accent-700 dark:text-accent-300 font-medium">
                                    · {{ group.period.name }}
                                </span>
                                <button
                                    type="button"
                                    @click="showMoveModal = true"
                                    class="inline-flex items-center gap-1 text-xs text-accent-600 hover:text-accent-700 dark:text-accent-400 hover:underline transition-colors"
                                >
                                    <TransferRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('groups.move_period') }}</span>
                                </button>
                            </p>
                        </div>
                    </div>

                    <!-- Header Quick Actions -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <Link :href="route('attendance.index', group.slug)">
                            <CpButton type="button" variant="secondary" class="inline-flex items-center gap-1.5 text-xs shadow-sm">
                                <CalendarMonthRegular class="w-4 h-4" />
                                <span>{{ t('attendance.title') }}</span>
                            </CpButton>
                        </Link>

                        <button
                            type="button"
                            @click="showBulkEnrollModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-accent-500 hover:bg-accent-600 text-white shadow-sm transition-colors"
                        >
                            <UserAddRegular class="w-4 h-4" />
                            <span>{{ t('groups.enroll_students') }}</span>
                        </button>

                        <Link :href="route('exports.history', { type: 'group', group_id: group.id })">
                            <CpButton type="button" variant="ghost" class="inline-flex items-center gap-1.5 text-xs">
                                <DownloadRegular class="w-4 h-4" />
                                <span>{{ t('exports.title') }}</span>
                            </CpButton>
                        </Link>

                        <CpButton
                            type="button"
                            variant="ghost"
                            class="text-xs"
                            @click="toggleArchive"
                        >
                            {{ group.is_archived ? t('groups.unarchive') : t('groups.archive') }}
                        </CpButton>
                    </div>
                </div>

                <!-- Metrics strip -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-4 border-t border-cafe-200/60 dark:border-cafe-800/60">
                    <div class="p-3 rounded-xl bg-white/70 dark:bg-surface-dark-2/60 border border-cafe-200/70 dark:border-cafe-700/60">
                        <span class="block text-[11px] font-semibold text-cafe-500 uppercase tracking-wider">{{ t('groups.students_enrolled') }}</span>
                        <span class="text-lg font-bold font-sans text-cafe-900 dark:text-cafe-100">{{ students.length }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-white/70 dark:bg-surface-dark-2/60 border border-cafe-200/70 dark:border-cafe-700/60">
                        <span class="block text-[11px] font-semibold text-cafe-500 uppercase tracking-wider">{{ t('grades.average') }}</span>
                        <span class="text-lg font-bold font-sans text-cafe-900 dark:text-cafe-100">
                            {{ averageGroupScore !== null ? `${averageGroupScore}%` : '—' }}
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-white/70 dark:bg-surface-dark-2/60 border border-cafe-200/70 dark:border-cafe-700/60">
                        <span class="block text-[11px] font-semibold text-cafe-500 uppercase tracking-wider">{{ t('groups.rubric_title') }}</span>
                        <span class="text-lg font-bold font-sans" :class="totalWeight === 100 ? 'text-state-success' : 'text-state-warning'">
                            {{ totalWeight }}%
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-white/70 dark:bg-surface-dark-2/60 border border-cafe-200/70 dark:border-cafe-700/60">
                        <span class="block text-[11px] font-semibold text-cafe-500 uppercase tracking-wider">{{ t('students.risk') }}</span>
                        <span class="text-lg font-bold font-sans" :class="atRiskCount > 0 ? 'text-state-danger' : 'text-cafe-700 dark:text-cafe-300'">
                            {{ atRiskCount }}
                        </span>
                    </div>
                </div>

                <!-- View Switch Tabs -->
                <div class="flex items-center gap-2 mt-4">
                    <button
                        type="button"
                        @click="currentTab = 'roster'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all"
                        :class="currentTab === 'roster'
                            ? 'bg-cafe-800 text-white dark:bg-cafe-100 dark:text-cafe-900 shadow-sm'
                            : 'text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200/60 dark:hover:bg-surface-dark-2'"
                    >
                        {{ t('groups.students_list') }} ({{ students.length }})
                    </button>
                    <button
                        type="button"
                        @click="currentTab = 'rubric'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all inline-flex items-center gap-1.5"
                        :class="currentTab === 'rubric'
                            ? 'bg-cafe-800 text-white dark:bg-cafe-100 dark:text-cafe-900 shadow-sm'
                            : 'text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200/60 dark:hover:bg-surface-dark-2'"
                    >
                        <span>{{ t('groups.rubric_title') }}</span>
                        <span
                            class="w-2 h-2 rounded-full"
                            :class="totalWeight === 100 ? 'bg-state-success' : 'bg-state-warning'"
                        />
                    </button>
                </div>
            </div>

            <!-- TAB 1: ROSTER & QUICK GRADING -->
            <div v-if="currentTab === 'roster'" class="space-y-6">
                <!-- Students Table Container -->
                <div class="overflow-hidden rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 shadow-sm">
                    <div class="p-4 sm:p-5 flex items-center justify-between border-b border-cafe-200/70 dark:border-cafe-800/80 bg-cafe-50/50 dark:bg-surface-dark-2/40">
                        <h2 class="text-sm font-serif font-bold text-cafe-900 dark:text-cafe-100">
                            {{ t('groups.students_list') }}
                        </h2>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="openGradeModalForStudent()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-cafe-100 hover:bg-cafe-200 dark:bg-surface-dark-2 dark:hover:bg-surface-dark-3 text-cafe-800 dark:text-cafe-100 border border-cafe-200 dark:border-cafe-700 transition-colors"
                            >
                                <CheckCircleRegular class="w-3.5 h-3.5 text-accent-600 dark:text-accent-400" />
                                <span>{{ t('groups.quick_grade') }}</span>
                            </button>
                            <button
                                type="button"
                                @click="openObsModalForStudent()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-cafe-100 hover:bg-cafe-200 dark:bg-surface-dark-2 dark:hover:bg-surface-dark-3 text-cafe-800 dark:text-cafe-100 border border-cafe-200 dark:border-cafe-700 transition-colors"
                            >
                                <ClipboardRegular class="w-3.5 h-3.5 text-accent-600 dark:text-accent-400" />
                                <span>{{ t('groups.quick_observe') }}</span>
                            </button>
                            <button
                                type="button"
                                @click="showBulkEnrollModal = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-accent-500 hover:bg-accent-600 text-white transition-colors shadow-sm"
                            >
                                <UserAddRegular class="w-3.5 h-3.5" />
                                <span>{{ t('groups.bulk_enroll') }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="students.length === 0" class="p-10 text-center text-cafe-500 dark:text-cafe-400">
                        <User4Regular class="w-10 h-10 mx-auto text-cafe-400 mb-2 opacity-60" />
                        <p class="text-sm font-medium">{{ t('groups.no_students') }}</p>
                        <button
                            type="button"
                            @click="showBulkEnrollModal = true"
                            class="mt-3 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-accent-500 text-white shadow-sm hover:bg-accent-600 transition-colors"
                        >
                            <UserAddRegular class="w-3.5 h-3.5" />
                            <span>{{ t('groups.enroll_students') }}</span>
                        </button>
                    </div>

                    <table v-else class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-cafe-200/80 dark:border-cafe-800/90 bg-cafe-100/50 dark:bg-surface-dark-2/60 text-xs font-semibold uppercase tracking-wider text-cafe-600 dark:text-cafe-300">
                                <th class="py-3 px-5">{{ t('students.name') }}</th>
                                <th class="py-3 px-5 text-center">{{ t('grades.average') }}</th>
                                <th class="py-3 px-5 text-center">{{ t('attendance.rate') }}</th>
                                <th class="py-3 px-5 text-center">{{ t('students.risk') }}</th>
                                <th class="py-3 px-5 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cafe-200/60 dark:divide-cafe-800/60">
                            <tr
                                v-for="student in students"
                                :key="student.id"
                                class="hover:bg-cafe-50/70 dark:hover:bg-surface-dark-2/40 transition-colors group"
                            >
                                <!-- Student Name: Nombre Apellido -->
                                <td class="py-3.5 px-5">
                                    <Link
                                        :href="route('students.show', student.slug)"
                                        class="font-medium text-cafe-900 dark:text-cafe-100 group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors inline-flex items-center gap-2"
                                    >
                                        <span>{{ student.first_name }} {{ student.last_name }}</span>
                                    </Link>
                                    <span
                                        v-if="student.summary?.has_absence_alert"
                                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-state-warning/15 text-state-warning"
                                        :title="t('attendance.absence_alert')"
                                    >
                                        {{ t('attendance.absence_alert') }}
                                    </span>
                                </td>

                                <!-- Grade Average -->
                                <td class="py-3.5 px-5 text-center font-mono text-xs">
                                    <span
                                        v-if="student.summary?.average !== null"
                                        class="font-semibold"
                                        :class="student.summary?.at_risk ? 'text-state-danger' : 'text-cafe-800 dark:text-cafe-200'"
                                    >
                                        {{ student.summary?.average }}%
                                    </span>
                                    <span v-else class="text-cafe-400">—</span>
                                </td>

                                <!-- Attendance Rate -->
                                <td class="py-3.5 px-5 text-center font-mono text-xs text-cafe-600 dark:text-cafe-300">
                                    {{ student.summary?.attendance?.rate !== null ? `${student.summary?.attendance?.rate}%` : '—' }}
                                </td>

                                <!-- Risk status -->
                                <td class="py-3.5 px-5 text-center">
                                    <span
                                        v-if="student.summary?.at_risk"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-state-danger/15 text-state-danger"
                                    >
                                        <AlertRegular class="w-3.5 h-3.5" />
                                        <span>{{ t('students.at_risk') }}</span>
                                    </span>
                                    <span v-else class="text-xs text-state-success font-medium">✓</span>
                                </td>

                                <!-- Direct ergonomic actions -->
                                <td class="py-3.5 px-5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Quick Grade -->
                                        <button
                                            type="button"
                                            @click="openGradeModalForStudent(student.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium text-cafe-700 dark:text-cafe-200 bg-cafe-100 dark:bg-surface-dark-2 hover:bg-accent-100 dark:hover:bg-surface-dark-3 hover:text-accent-800 transition-colors"
                                            :title="t('groups.quick_grade')"
                                        >
                                            <CheckCircleRegular class="w-3.5 h-3.5 text-accent-600 dark:text-accent-400" />
                                            <span>{{ t('groups.quick_grade') }}</span>
                                        </button>

                                        <!-- Quick Observe -->
                                        <button
                                            type="button"
                                            @click="openObsModalForStudent(student.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium text-cafe-700 dark:text-cafe-200 bg-cafe-100 dark:bg-surface-dark-2 hover:bg-cafe-200 dark:hover:bg-surface-dark-3 transition-colors"
                                            :title="t('groups.quick_observe')"
                                        >
                                            <ClipboardRegular class="w-3.5 h-3.5 text-accent-600 dark:text-accent-400" />
                                            <span>{{ t('groups.quick_observe') }}</span>
                                        </button>

                                        <!-- Dar de baja (Unenroll) -->
                                        <button
                                            type="button"
                                            @click="unenrollStudent(student)"
                                            class="p-1.5 rounded-md text-cafe-400 hover:text-state-danger hover:bg-state-danger/10 transition-colors"
                                            :title="t('groups.unenroll')"
                                        >
                                            <UserRemoveRegular class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: RUBRIC & WEIGHTS (CATEGORÍAS) -->
            <div v-if="currentTab === 'rubric'" class="space-y-6">
                <div class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 p-6 shadow-sm space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-cafe-200/80 dark:border-cafe-800 pb-4">
                        <div>
                            <h2 class="text-lg font-serif font-bold text-cafe-900 dark:text-cafe-50">
                                {{ t('groups.rubric_title') }}
                            </h2>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                {{ t('groups.rubric_desc') }}
                            </p>
                        </div>

                        <CpButton
                            type="button"
                            class="inline-flex items-center gap-2 text-xs shadow-sm"
                            @click="showCategoryModal = true"
                        >
                            <AddCircleRegular class="w-4 h-4" />
                            <span>{{ t('common.add') }}</span>
                        </CpButton>
                    </div>

                    <!-- Visual Rubric Progress Bar -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-cafe-700 dark:text-cafe-300">{{ t('grades.total_weight') }}</span>
                            <span :class="totalWeight === 100 ? 'text-state-success font-bold' : 'text-state-warning'">
                                {{ totalWeight }}% / 100%
                                <span v-if="totalWeight === 100">({{ t('groups.rubric_complete') }})</span>
                                <span v-else>({{ t('groups.rubric_incomplete') }})</span>
                            </span>
                        </div>

                        <div class="w-full h-3 rounded-full bg-cafe-100 dark:bg-surface-dark-2 overflow-hidden flex">
                            <div
                                v-for="(cat, idx) in categories"
                                :key="cat.id"
                                class="h-full transition-all duration-300"
                                :style="{ width: `${cat.weight}%` }"
                                :class="[
                                    idx % 4 === 0 ? 'bg-accent-500' : '',
                                    idx % 4 === 1 ? 'bg-amber-500' : '',
                                    idx % 4 === 2 ? 'bg-state-success' : '',
                                    idx % 4 === 3 ? 'bg-sky-500' : '',
                                ]"
                                :title="`${cat.name}: ${cat.weight}%`"
                            />
                        </div>
                    </div>

                    <!-- Categories list -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5 pt-2">
                        <div
                            v-for="cat in categories"
                            :key="cat.id"
                            class="p-4 rounded-xl border border-cafe-200 dark:border-cafe-700/80 bg-cafe-50/50 dark:bg-surface-dark-2/40 flex items-center justify-between"
                        >
                            <div>
                                <span class="font-semibold text-sm text-cafe-900 dark:text-cafe-100 block">{{ cat.name }}</span>
                                <span class="text-xs font-mono text-accent-700 dark:text-accent-300 font-bold mt-0.5 block">
                                    {{ cat.weight }}%
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="deleteCategory(cat.id)"
                                class="p-1.5 rounded-lg text-cafe-400 hover:text-state-danger hover:bg-state-danger/10 transition-colors"
                                :title="t('common.delete')"
                            >
                                <Delete2Regular class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: Bulk Student Enrollment -->
            <div
                v-if="showBulkEnrollModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                @click.self="showBulkEnrollModal = false"
            >
                <div class="w-full max-w-lg bg-white dark:bg-surface-dark-1 rounded-2xl border border-cafe-200 dark:border-cafe-700 shadow-2xl p-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 shrink-0">
                            <UserAddRegular class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-serif font-bold text-cafe-900 dark:text-cafe-50">
                                {{ t('groups.bulk_enroll_title') }}
                            </h3>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-1 leading-relaxed">
                                {{ t('groups.bulk_enroll_desc') }}
                            </p>
                        </div>
                    </div>

                    <!-- Search filter in modal -->
                    <div class="relative">
                        <input
                            v-model="bulkSearchQuery"
                            type="text"
                            :placeholder="t('students.name')"
                            class="cp-input text-xs py-2 pl-8 pr-3"
                        />
                        <Search2Regular class="w-3.5 h-3.5 text-cafe-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                    </div>

                    <!-- Selection actions toolbar -->
                    <div class="flex items-center justify-between text-xs pt-1 border-t border-cafe-100 dark:border-cafe-800">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="selectAllAvailable"
                                class="text-accent-600 hover:text-accent-700 dark:text-accent-400 hover:underline font-medium"
                            >
                                {{ t('groups.select_all') }}
                            </button>
                            <span class="text-cafe-300">·</span>
                            <button
                                type="button"
                                @click="deselectAllAvailable"
                                class="text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 hover:underline"
                            >
                                {{ t('groups.deselect_all') }}
                            </button>
                        </div>
                        <span class="font-semibold text-cafe-700 dark:text-cafe-300">
                            {{ t('groups.selected_count', { count: selectedStudentIds.length }) }}
                        </span>
                    </div>

                    <!-- Available Students Checkbox List -->
                    <div class="max-h-64 overflow-y-auto divide-y divide-cafe-100 dark:divide-cafe-800 border border-cafe-200 dark:border-cafe-700 rounded-xl p-1 bg-cafe-50/50 dark:bg-surface-dark-2/40">
                        <div
                            v-if="filteredAvailableStudents.length === 0"
                            class="py-8 text-center text-xs text-cafe-500 dark:text-cafe-400"
                        >
                            {{ t('groups.no_available_students') }}
                        </div>
                        <label
                            v-for="student in filteredAvailableStudents"
                            :key="student.id"
                            class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-white dark:hover:bg-surface-dark-2 cursor-pointer transition-colors select-none"
                        >
                            <input
                                type="checkbox"
                                :value="student.id"
                                :checked="selectedStudentIds.includes(student.id)"
                                @change="toggleSelectStudent(student.id)"
                                class="rounded border-cafe-300 text-accent-500 focus:ring-accent-400 h-4 w-4"
                            />
                            <span class="text-xs font-medium text-cafe-800 dark:text-cafe-100">
                                {{ student.first_name }} {{ student.last_name }}
                            </span>
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <CpButton type="button" variant="ghost" @click="showBulkEnrollModal = false">
                            {{ t('common.cancel') }}
                        </CpButton>
                        <CpButton
                            type="button"
                            :disabled="selectedStudentIds.length === 0 || bulkForm.processing"
                            :loading="bulkForm.processing"
                            @click="submitBulkEnroll"
                        >
                            {{ t('groups.enroll_students') }} ({{ selectedStudentIds.length }})
                        </CpButton>
                    </div>
                </div>
            </div>

            <!-- MODAL: Move Group Period -->
            <div
                v-if="showMoveModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                @click.self="showMoveModal = false"
            >
                <div class="w-full max-w-md bg-white dark:bg-surface-dark-1 rounded-2xl border border-cafe-200 dark:border-cafe-700 shadow-2xl p-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 shrink-0">
                            <TransferRegular class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-serif font-bold text-cafe-900 dark:text-cafe-50">
                                {{ t('groups.move_period_title') }}
                            </h3>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-1 leading-relaxed">
                                {{ t('groups.move_period_desc') }}
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="submitMovePeriod" class="space-y-4 pt-1">
                        <div>
                            <label class="cp-label text-xs font-semibold">{{ t('groups.select_period') }}</label>
                            <CpSelect
                                id="move_period_select"
                                v-model="moveForm.period_id"
                                :options="allPeriodOptions"
                                required
                            />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <CpButton type="button" variant="ghost" @click="showMoveModal = false">
                                {{ t('common.cancel') }}
                            </CpButton>
                            <CpButton type="submit" :loading="moveForm.processing" :disabled="moveForm.processing">
                                {{ t('groups.move_period') }}
                            </CpButton>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Quick Student Grade Entry -->
            <div
                v-if="showGradeModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                @click.self="showGradeModal = false"
            >
                <div class="w-full max-w-lg bg-white dark:bg-surface-dark-1 rounded-2xl border border-cafe-200 dark:border-cafe-700 shadow-2xl p-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 shrink-0">
                            <CheckCircleRegular class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-serif font-bold text-cafe-900 dark:text-cafe-50">
                                {{ t('grades.add') }}
                            </h3>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                {{ group.name }}
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="submitGrade" class="space-y-4 pt-1">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <CpSelect
                                id="grade_student"
                                v-model="gradeForm.student_id"
                                :label="t('students.name')"
                                :options="studentOptions"
                                :placeholder="t('grades.select_student')"
                                :error="gradeForm.errors.student_id"
                                required
                            />
                            <CpSelect
                                id="grade_category"
                                v-model="gradeForm.category_id"
                                :label="t('grades.category')"
                                :options="categoryOptions"
                                :placeholder="t('grades.select_category')"
                                :error="gradeForm.errors.category_id"
                                required
                            />
                        </div>

                        <div>
                            <CpInput
                                id="grade_title"
                                v-model="gradeForm.title"
                                :label="t('grades.title_label')"
                                :error="gradeForm.errors.title"
                                placeholder="Ej. Examen Parcial 1 / Tarea de Investigación"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <CpInput
                                id="grade_score"
                                v-model="gradeForm.score"
                                :label="t('grades.score')"
                                type="number"
                                :error="gradeForm.errors.score"
                                required
                            />
                            <CpInput
                                id="grade_max"
                                v-model="gradeForm.max_score"
                                :label="t('grades.max_score')"
                                type="number"
                                :error="gradeForm.errors.max_score"
                                required
                            />
                            <CpInput
                                id="grade_date"
                                v-model="gradeForm.date"
                                :label="t('grades.date')"
                                type="date"
                                :error="gradeForm.errors.date"
                                required
                            />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <CpButton type="button" variant="ghost" @click="showGradeModal = false">
                                {{ t('common.cancel') }}
                            </CpButton>
                            <CpButton type="submit" :loading="gradeForm.processing" :disabled="gradeForm.processing">
                                {{ t('grades.submit') }}
                            </CpButton>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Quick Observation Entry -->
            <div
                v-if="showObsModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                @click.self="showObsModal = false"
            >
                <div class="w-full max-w-lg bg-white dark:bg-surface-dark-1 rounded-2xl border border-cafe-200 dark:border-cafe-700 shadow-2xl p-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 shrink-0">
                            <ClipboardRegular class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-serif font-bold text-cafe-900 dark:text-cafe-50">
                                {{ t('observations.add') }}
                            </h3>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                {{ group.name }}
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="submitObservation" class="space-y-4 pt-1">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <CpSelect
                                id="obs_student"
                                v-model="obsForm.student_id"
                                :label="t('students.name')"
                                :options="studentOptions"
                                :placeholder="t('observations.select_student')"
                                :error="obsForm.errors.student_id"
                                required
                            />
                            <CpSelect
                                id="obs_type"
                                v-model="obsForm.type"
                                :label="t('observations.type')"
                                :options="obsTypeOptions"
                                :error="obsForm.errors.type"
                                required
                            />
                        </div>

                        <div>
                            <label for="obs_content" class="cp-label text-xs font-semibold">{{ t('observations.content') }}</label>
                            <textarea
                                id="obs_content"
                                v-model="obsForm.content"
                                rows="4"
                                class="cp-input text-xs"
                                placeholder="Escribe observaciones pedagógicas, acuerdos o notas sobre el estudiante..."
                                required
                            />
                            <p v-if="obsForm.errors.content" class="cp-error text-xs mt-1">{{ obsForm.errors.content }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <CpButton type="button" variant="ghost" @click="showObsModal = false">
                                {{ t('common.cancel') }}
                            </CpButton>
                            <CpButton type="submit" :loading="obsForm.processing" :disabled="obsForm.processing">
                                {{ t('observations.submit') }}
                            </CpButton>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: New Grade Category / Rubric Item -->
            <div
                v-if="showCategoryModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                @click.self="showCategoryModal = false"
            >
                <div class="w-full max-w-md bg-white dark:bg-surface-dark-1 rounded-2xl border border-cafe-200 dark:border-cafe-700 shadow-2xl p-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 shrink-0">
                            <AddCircleRegular class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-serif font-bold text-cafe-900 dark:text-cafe-50">
                                {{ t('grades.categories') }}
                            </h3>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                {{ t('groups.rubric_desc') }}
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="submitCategory" class="space-y-4 pt-1">
                        <CpInput
                            id="cat_name"
                            v-model="categoryForm.name"
                            :label="t('grades.category_name')"
                            :error="categoryForm.errors.name"
                            placeholder="Ej. Exámenes, Tareas, Proyecto Final"
                            required
                        />
                        <CpInput
                            id="cat_weight"
                            v-model="categoryForm.weight"
                            :label="t('grades.weight') + ' (%)'"
                            type="number"
                            :error="categoryForm.errors.weight"
                            placeholder="Ej. 30"
                            required
                        />

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <CpButton type="button" variant="ghost" @click="showCategoryModal = false">
                                {{ t('common.cancel') }}
                            </CpButton>
                            <CpButton type="submit" :loading="categoryForm.processing" :disabled="categoryForm.processing">
                                {{ t('common.add') }}
                            </CpButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
