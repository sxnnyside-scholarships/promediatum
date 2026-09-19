<script setup lang="ts">
/**
 * Students Show — Pedagogical Student Profile, Analytics & Observations
 *
 * Fully unified with the Café Pedagógico Design System:
 * rounded-2xl cards, restful pedagogical gradient, harmonic warm palette,
 * initials pseudo-avatar, SVG progression charts, category rubric distributions,
 * attendance gauge, and pedagogical observations log.
 */

import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AddCircleRegular,
    AlertRegular,
    Book2Regular,
    CalendarMonthRegular,
    ChartBarRegular,
    ChartLineRegular,
    CheckCircleRegular,
    CloseCircleRegular,
    Delete2Regular,
    Edit2Regular,
    GroupRegular,
    LeftSmallRegular,
    MailRegular,
    NotebookRegular,
    PhoneRegular,
    TimeRegular,
    User4Regular,
} from '@mingcute/vue/core-regular';
import { computed, ref } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import CpSelect from '@/Components/CpSelect.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface GroupInfo {
    id: number;
    name: string;
    slug?: string;
    period_id?: number;
    period?: {
        id: number;
        name: string;
        is_active?: boolean;
    };
    pivot?: {
        period_id?: number;
    };
}

interface CategoryBreakdown {
    id: number;
    name: string;
    weight: number;
    average: number | null;
    weighted_contribution: number | null;
}

interface AttendanceStats {
    total: number;
    present: number;
    absent: number;
    justified: number;
    rate: number | null;
}

interface GroupSummary {
    group: GroupInfo;
    summary: {
        average: number | null;
        attendance: AttendanceStats;
        absence_streak: number;
        has_absence_alert: boolean;
        at_risk: boolean;
    };
    categories: CategoryBreakdown[];
}

interface GradeHistoryItem {
    id: number;
    title: string;
    score: number;
    max_score: number;
    percentage: number;
    date: string | null;
    formatted_date: string | null;
    category_name?: string | null;
    category_id: number;
    group_name?: string | null;
    group_id: number;
}

interface ObservationItem {
    id: number;
    student_id: number;
    group_id: number;
    type: 'performance' | 'behavior' | 'achievement' | 'followup';
    content: string;
    status: 'pending' | 'resolved';
    created_at: string;
    group?: GroupInfo;
}

interface StudentModel {
    id: number;
    first_name: string;
    last_name: string;
    slug: string;
    full_name: string;
    initials: string;
    email?: string | null;
    phone?: string | null;
    guardian_name?: string | null;
    notes?: string | null;
    groups?: GroupInfo[];
}

const props = defineProps<{
    student: StudentModel;
    groupSummaries: GroupSummary[];
    gradesHistory?: GradeHistoryItem[];
    allGroups?: GroupInfo[];
    observations: ObservationItem[];
}>();

const { t } = useTranslations();

// Active tab: 'analytics' | 'observations' | 'groups'
const activeTab = ref<'analytics' | 'observations' | 'groups'>('analytics');

// Selected group filter for chart progression
const chartGroupFilter = ref<string>('all');

const filteredGradesHistory = computed(() => {
    if (!props.gradesHistory) return [];
    if (chartGroupFilter.value === 'all') {
        return props.gradesHistory;
    }
    const gid = Number(chartGroupFilter.value);
    return props.gradesHistory.filter((g) => g.group_id === gid);
});

// Overall summary calculations
const globalAverage = computed(() => {
    const avgs = props.groupSummaries
        .map((gs) => gs.summary.average)
        .filter((a): a is number => a !== null);
    if (avgs.length === 0) return null;
    const sum = avgs.reduce((acc, curr) => acc + curr, 0);
    return Math.round((sum / avgs.length) * 10) / 10;
});

const globalAttendanceRate = computed(() => {
    let totalClasses = 0;
    let totalPresent = 0;
    for (const gs of props.groupSummaries) {
        totalClasses += gs.summary.attendance.total;
        totalPresent += gs.summary.attendance.present;
    }
    if (totalClasses === 0) return null;
    return Math.round((totalPresent / totalClasses) * 1000) / 10;
});

const hasAnyAlert = computed(() => {
    return props.groupSummaries.some((gs) => gs.summary.at_risk || gs.summary.has_absence_alert);
});

// SVG Line Chart Calculation
const chartPoints = computed(() => {
    const list = filteredGradesHistory.value;
    if (list.length === 0) return { path: '', area: '', points: [] };

    const width = 560;
    const height = 150;
    const paddingX = 35;
    const paddingY = 25;

    const innerW = width - paddingX * 2;
    const innerH = height - paddingY * 2;

    const pts = list.map((item, index) => {
        const x = list.length === 1 ? width / 2 : paddingX + (index / (list.length - 1)) * innerW;
        const pct = Math.max(0, Math.min(100, item.percentage));
        const y = paddingY + innerH - (pct / 100) * innerH;
        return {
            x: Math.round(x * 10) / 10,
            y: Math.round(y * 10) / 10,
            item,
        };
    });

    // Build SVG Path
    let path = `M ${pts[0].x} ${pts[0].y}`;
    for (let i = 1; i < pts.length; i++) {
        const prev = pts[i - 1];
        const curr = pts[i];
        const midX = (prev.x + curr.x) / 2;
        path += ` C ${midX} ${prev.y}, ${midX} ${curr.y}, ${curr.x} ${curr.y}`;
    }

    const baselineY = height - paddingY;
    const area = `${path} L ${pts[pts.length - 1].x} ${baselineY} L ${pts[0].x} ${baselineY} Z`;

    return { path, area, points: pts };
});

// Observation form state
const showObsModal = ref(false);
const obsForm = useForm({
    student_id: props.student.id,
    group_id: '',
    type: 'performance' as 'performance' | 'behavior' | 'achievement' | 'followup',
    content: '',
});

const obsTypeOptions = [
    { value: 'performance', label: t('observations.type_performance') },
    { value: 'behavior', label: t('observations.type_behavior') },
    { value: 'achievement', label: t('observations.type_achievement') },
    { value: 'followup', label: t('observations.type_followup') },
];

const availableGroupOptions = computed(() => {
    return props.groupSummaries.map((gs) => ({
        value: String(gs.group.id),
        label: `${gs.group.name} (${gs.group.period?.name || ''})`,
    }));
});

function submitObservation() {
    obsForm.post(route('observations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            obsForm.reset('group_id', 'content');
            obsForm.type = 'performance';
            showObsModal.value = false;
        },
    });
}

function toggleResolved(obs: ObservationItem) {
    router.post(route('observations.toggle-resolved', obs.id), {}, { preserveScroll: true });
}

function deleteObservation(obs: ObservationItem) {
    if (confirm(t('common.confirm_delete'))) {
        router.delete(route('observations.destroy', obs.id), {
            preserveScroll: true,
        });
    }
}

// Edit Profile Modal
const showEditProfileModal = ref(false);
const editForm = useForm({
    first_name: props.student.first_name,
    last_name: props.student.last_name,
    guardian_name: props.student.guardian_name || '',
    email: props.student.email || '',
    phone: props.student.phone || '',
    notes: props.student.notes || '',
    group_ids: props.student.groups ? props.student.groups.map((g) => g.id) : [],
});

function openEditModal() {
    editForm.first_name = props.student.first_name;
    editForm.last_name = props.student.last_name;
    editForm.guardian_name = props.student.guardian_name || '';
    editForm.email = props.student.email || '';
    editForm.phone = props.student.phone || '';
    editForm.notes = props.student.notes || '';
    editForm.group_ids = props.student.groups ? props.student.groups.map((g) => g.id) : [];
    showEditProfileModal.value = true;
}

function toggleEditGroup(groupId: number) {
    const idx = editForm.group_ids.indexOf(groupId);
    if (idx >= 0) {
        editForm.group_ids.splice(idx, 1);
    } else {
        editForm.group_ids.push(groupId);
    }
}

function submitEditProfile() {
    editForm.patch(route('students.update', props.student.slug), {
        preserveScroll: true,
        onSuccess: () => {
            showEditProfileModal.value = false;
        },
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="student.full_name" />

        <div class="space-y-6 max-w-7xl">
            <!-- Back button -->
            <div>
                <Link
                    :href="route('students.index')"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-cafe-600 dark:text-cafe-400 hover:text-cafe-900 dark:hover:text-cafe-100 transition-colors"
                >
                    <LeftSmallRegular class="w-4 h-4" />
                    <span>{{ t('students.back') }}</span>
                </Link>
            </div>

            <!-- Student Profile Hero Header Card -->
            <div
                class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm"
            >
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-4 sm:gap-5">
                        <!-- Initials Pseudo-Avatar -->
                        <StudentAvatar :student="student" size="xl" />

                        <div class="space-y-1.5 min-w-0">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h1 class="font-serif text-2xl md:text-3xl text-cafe-900 dark:text-cafe-50 font-bold tracking-tight">
                                    {{ student.full_name }}
                                </h1>
                                <span
                                    v-if="hasAnyAlert"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs bg-state-warning/15 text-state-warning font-bold border border-state-warning/30"
                                >
                                    <AlertRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('students.at_risk') }}</span>
                                </span>
                            </div>

                            <p v-if="student.guardian_name" class="text-xs text-cafe-600 dark:text-cafe-400 flex items-center gap-1.5">
                                <User4Regular class="w-3.5 h-3.5 text-accent-600 dark:text-accent-400" />
                                <span>{{ t('students.guardian_name') }}: <strong>{{ student.guardian_name }}</strong></span>
                            </p>

                            <!-- Quick contact tags -->
                            <div class="flex items-center gap-3 pt-1 text-xs text-cafe-600 dark:text-cafe-300 flex-wrap">
                                <span v-if="student.email" class="inline-flex items-center gap-1">
                                    <MailRegular class="w-3.5 h-3.5 text-cafe-400" />
                                    <span>{{ student.email }}</span>
                                </span>
                                <span v-if="student.phone" class="inline-flex items-center gap-1">
                                    <PhoneRegular class="w-3.5 h-3.5 text-cafe-400" />
                                    <span>{{ student.phone }}</span>
                                </span>
                                <span class="inline-flex items-center gap-1 text-cafe-500 font-medium">
                                    <GroupRegular class="w-3.5 h-3.5 text-cafe-400" />
                                    <span>{{ student.groups?.length || 0 }} {{ t('students.groups_count') }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-center gap-2.5 self-start md:self-auto">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-cafe-100 hover:bg-accent-100 dark:bg-surface-dark-2 dark:hover:bg-surface-dark-3 text-cafe-700 dark:text-cafe-200 hover:text-accent-800 dark:hover:text-accent-300 border border-cafe-200 dark:border-cafe-700 transition-colors"
                            @click="openEditModal"
                        >
                            <Edit2Regular class="w-3.5 h-3.5" />
                            <span>{{ t('students.edit_profile') }}</span>
                        </button>

                        <Link :href="route('exports.history', { type: 'student', student_id: student.id })">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-cafe-100 hover:bg-accent-100 dark:bg-surface-dark-2 dark:hover:bg-surface-dark-3 text-cafe-700 dark:text-cafe-200 hover:text-accent-800 dark:hover:text-accent-300 border border-cafe-200 dark:border-cafe-700 transition-colors"
                            >
                                <span>{{ t('exports.title') }}</span>
                            </button>
                        </Link>
                    </div>
                </div>

                <!-- Metrics Overview Banner -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-5 border-t border-cafe-200/60 dark:border-cafe-800/60">
                    <div class="rounded-xl bg-white/80 dark:bg-surface-dark-3 p-3.5 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs">
                        <p class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">{{ t('students.global_average') }}</p>
                        <p
                            class="text-xl font-bold font-serif mt-0.5"
                            :class="[
                                globalAverage === null
                                    ? 'text-cafe-400'
                                    : globalAverage < 60
                                      ? 'text-state-danger'
                                      : globalAverage < 70
                                        ? 'text-state-warning'
                                        : 'text-state-success',
                            ]"
                        >
                            {{ globalAverage !== null ? `${globalAverage}%` : '—' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-white/80 dark:bg-surface-dark-3 p-3.5 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs">
                        <p class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">{{ t('students.total_attendance') }}</p>
                        <p class="text-xl font-bold font-serif text-cafe-900 dark:text-cafe-100 mt-0.5">
                            {{ globalAttendanceRate !== null ? `${globalAttendanceRate}%` : '—' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-white/80 dark:bg-surface-dark-3 p-3.5 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs">
                        <p class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">{{ t('students.groups') }}</p>
                        <p class="text-xl font-bold font-serif text-cafe-900 dark:text-cafe-100 mt-0.5">
                            {{ student.groups?.length || 0 }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-white/80 dark:bg-surface-dark-3 p-3.5 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs">
                        <p class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">{{ t('students.observations_count') }}</p>
                        <p class="text-xl font-bold font-serif text-accent-600 dark:text-accent-400 mt-0.5">
                            {{ observations.length }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pedagogical Notes Quick Card (if notes exist) -->
            <div
                v-if="student.notes"
                class="rounded-2xl border border-amber-200/80 dark:border-amber-900/60 bg-gradient-to-b from-amber-50/70 to-white dark:from-surface-dark-2 dark:to-surface-dark-1 p-5 shadow-sm space-y-2"
            >
                <div class="flex items-center gap-2.5 text-amber-700 dark:text-amber-400">
                    <div class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/70">
                        <NotebookRegular class="w-4 h-4" />
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider">
                        {{ t('students.notes') }}
                    </h3>
                </div>
                <p class="text-xs text-cafe-700 dark:text-cafe-300 leading-relaxed whitespace-pre-line pl-1">
                    {{ student.notes }}
                </p>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="border-b border-cafe-200 dark:border-cafe-800 flex items-center gap-6 text-sm font-medium">
                <button
                    type="button"
                    :class="[
                        'pb-3 border-b-2 transition-colors flex items-center gap-2',
                        activeTab === 'analytics'
                            ? 'border-accent-600 dark:border-accent-400 text-accent-700 dark:text-accent-300 font-bold'
                            : 'border-transparent text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200',
                    ]"
                    @click="activeTab = 'analytics'"
                >
                    <ChartLineRegular class="w-4 h-4" />
                    <span>{{ t('students.performance_charts') }}</span>
                </button>

                <button
                    type="button"
                    :class="[
                        'pb-3 border-b-2 transition-colors flex items-center gap-2',
                        activeTab === 'observations'
                            ? 'border-accent-600 dark:border-accent-400 text-accent-700 dark:text-accent-300 font-bold'
                            : 'border-transparent text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200',
                    ]"
                    @click="activeTab = 'observations'"
                >
                    <NotebookRegular class="w-4 h-4" />
                    <span>{{ t('observations.title') }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs bg-cafe-200 dark:bg-surface-dark-3 text-cafe-700 dark:text-cafe-300 font-bold">
                        {{ observations.length }}
                    </span>
                </button>

                <button
                    type="button"
                    :class="[
                        'pb-3 border-b-2 transition-colors flex items-center gap-2',
                        activeTab === 'groups'
                            ? 'border-accent-600 dark:border-accent-400 text-accent-700 dark:text-accent-300 font-bold'
                            : 'border-transparent text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200',
                    ]"
                    @click="activeTab = 'groups'"
                >
                    <GroupRegular class="w-4 h-4" />
                    <span>{{ t('students.assigned_groups') }}</span>
                </button>
            </div>

            <!-- Tab 1: Analytics & Performance Charts -->
            <div v-if="activeTab === 'analytics'" class="space-y-8">
                <!-- Section: Chronological Grade Timeline Curve -->
                <div class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 p-6 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50">
                                <ChartLineRegular class="w-5 h-5" />
                            </div>
                            <div>
                                <h2 class="font-serif text-base font-bold text-cafe-900 dark:text-cafe-100">
                                    {{ t('students.grades_timeline') }}
                                </h2>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                    {{ filteredGradesHistory.length }} {{ t('grades.title').toLowerCase() }}
                                </p>
                            </div>
                        </div>

                        <!-- Filter by group for chart -->
                        <div v-if="groupSummaries.length > 1" class="min-w-[180px]">
                            <select
                                v-model="chartGroupFilter"
                                class="w-full px-3 py-1.5 text-xs bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-800 dark:text-cafe-200 focus:outline-none focus:ring-1 focus:ring-accent-500"
                            >
                                <option value="all">{{ t('students.all_groups') }}</option>
                                <option v-for="gs in groupSummaries" :key="gs.group.id" :value="String(gs.group.id)">
                                    {{ gs.group.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- SVG Chart -->
                    <div v-if="filteredGradesHistory.length > 0" class="pt-2">
                        <div class="w-full overflow-x-auto">
                            <div class="min-w-[500px]">
                                <svg
                                    viewBox="0 0 560 150"
                                    class="w-full h-44 overflow-visible"
                                    aria-label="Student grade progression"
                                >
                                    <defs>
                                        <linearGradient id="gradeGradient" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#C47434" stop-opacity="0.25" />
                                            <stop offset="100%" stop-color="#C47434" stop-opacity="0.0" />
                                        </linearGradient>
                                    </defs>

                                    <!-- Grid lines (100%, 70%, 60%) -->
                                    <line x1="35" y1="25" x2="525" y2="25" stroke="#E8DDD0" stroke-dasharray="3,3" class="dark:stroke-neutral-800" />
                                    <text x="25" y="28" font-size="9" fill="#9C8468" text-anchor="end">100%</text>

                                    <line x1="35" y1="55" x2="525" y2="55" stroke="#E8DDD0" stroke-dasharray="3,3" class="dark:stroke-neutral-800" />
                                    <text x="25" y="58" font-size="9" fill="#9C8468" text-anchor="end">70%</text>

                                    <line x1="35" y1="85" x2="525" y2="85" stroke="#ECC8C0" stroke-dasharray="3,3" class="dark:stroke-red-950/60" />
                                    <text x="25" y="88" font-size="9" fill="#B85C4A" text-anchor="end">60%</text>

                                    <line x1="35" y1="125" x2="525" y2="125" stroke="#E8DDD0" class="dark:stroke-neutral-800" />

                                    <!-- Gradient Area fill -->
                                    <path
                                        v-if="chartPoints.area"
                                        :d="chartPoints.area"
                                        fill="url(#gradeGradient)"
                                    />

                                    <!-- Smooth Line -->
                                    <path
                                        v-if="chartPoints.path"
                                        :d="chartPoints.path"
                                        fill="none"
                                        stroke="#C47434"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="dark:stroke-accent-400"
                                    />

                                    <!-- Interactive Data Points -->
                                    <g v-for="(pt, idx) in chartPoints.points" :key="idx">
                                        <circle
                                            :cx="pt.x"
                                            :cy="pt.y"
                                            r="4.5"
                                            fill="#FAF7F4"
                                            stroke="#C47434"
                                            stroke-width="2.5"
                                            class="dark:fill-surface-dark-1 dark:stroke-accent-400 hover:r-6 transition-all cursor-pointer"
                                        />
                                        <text
                                            :x="pt.x"
                                            :y="pt.y - 8"
                                            font-size="9"
                                            font-weight="bold"
                                            fill="#5C4A36"
                                            text-anchor="middle"
                                            class="dark:fill-cafe-200 pointer-events-none"
                                        >
                                            {{ pt.item.percentage }}%
                                        </text>
                                        <text
                                            :x="pt.x"
                                            y="142"
                                            font-size="8"
                                            fill="#9C8468"
                                            text-anchor="middle"
                                            class="pointer-events-none"
                                        >
                                            {{ pt.item.formatted_date || `#${idx + 1}` }}
                                        </text>
                                    </g>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-xs text-cafe-500 dark:text-cafe-400">
                        {{ t('students.no_grades_yet') }}
                    </div>
                </div>

                <!-- Section: Category Rubric Breakdown per Group -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div
                        v-for="gs in groupSummaries"
                        :key="gs.group.id"
                        class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 p-6 shadow-sm space-y-4"
                    >
                        <div class="flex items-center justify-between border-b border-cafe-100 dark:border-cafe-800 pb-3">
                            <div>
                                <Link
                                    :href="route('groups.show', gs.group.slug || gs.group.id)"
                                    class="font-serif font-bold text-base text-cafe-900 dark:text-cafe-100 hover:text-accent-600 transition-colors"
                                >
                                    {{ gs.group.name }}
                                </Link>
                                <span class="text-xs text-cafe-500 dark:text-cafe-400 block">{{ gs.group.period?.name }}</span>
                            </div>

                            <span
                                :class="[
                                    'font-bold text-sm px-2.5 py-0.5 rounded-lg',
                                    gs.summary.average === null
                                        ? 'bg-cafe-100 text-cafe-500 dark:bg-surface-dark-3 dark:text-cafe-400'
                                        : gs.summary.average < 60
                                          ? 'bg-state-danger/15 text-state-danger font-bold'
                                          : gs.summary.average < 70
                                            ? 'bg-state-warning/15 text-state-warning font-bold'
                                            : 'bg-state-success/15 text-state-success font-bold',
                                ]"
                            >
                                {{ gs.summary.average !== null ? `${gs.summary.average}%` : '—' }}
                            </span>
                        </div>

                        <!-- Rubric Categories -->
                        <div class="space-y-3">
                            <p class="text-xs font-bold text-cafe-700 dark:text-cafe-200 uppercase tracking-wider">
                                {{ t('students.categories_breakdown') }}
                            </p>

                            <div v-if="gs.categories?.length" class="space-y-2.5">
                                <div v-for="cat in gs.categories" :key="cat.id" class="space-y-1">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-cafe-800 dark:text-cafe-200 font-medium">
                                            {{ cat.name }} <span class="text-cafe-400 font-normal">({{ cat.weight }}%)</span>
                                        </span>
                                        <span class="font-bold text-cafe-700 dark:text-cafe-300">
                                            {{ cat.average !== null ? `${cat.average}%` : '—' }}
                                        </span>
                                    </div>
                                    <!-- Progress bar -->
                                    <div class="w-full bg-cafe-100 dark:bg-surface-dark-3 h-2 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-300"
                                            :class="[
                                                cat.average === null
                                                    ? 'w-0'
                                                    : cat.average < 60
                                                      ? 'bg-state-danger'
                                                      : cat.average < 70
                                                        ? 'bg-state-warning'
                                                        : 'bg-accent-600 dark:bg-accent-400',
                                            ]"
                                            :style="{ width: `${Math.min(100, cat.average || 0)}%` }"
                                        />
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-xs text-cafe-400">
                                {{ t('grades.no_categories') }}
                            </p>
                        </div>

                        <!-- Attendance Mini Breakdown -->
                        <div class="pt-3 border-t border-cafe-100 dark:border-cafe-800 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-cafe-700 dark:text-cafe-200 uppercase tracking-wider">
                                    {{ t('attendance.title') }}
                                </span>
                                <span class="font-bold text-cafe-800 dark:text-cafe-100">
                                    {{ gs.summary.attendance.rate !== null ? `${gs.summary.attendance.rate}%` : '—' }}
                                </span>
                            </div>

                            <div v-if="gs.summary.has_absence_alert" class="p-2.5 rounded-xl bg-state-warning/15 border border-state-warning/30 flex items-center gap-2 text-xs text-state-warning font-bold">
                                <AlertRegular class="w-4 h-4 shrink-0" />
                                <span>{{ gs.summary.absence_streak }} {{ t('students.consecutive_absences') }}</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                <div class="p-2 rounded-xl bg-cafe-50 dark:bg-surface-dark-2 border border-cafe-200/60 dark:border-cafe-700/60">
                                    <span class="text-cafe-400 block text-[10px] uppercase font-semibold">{{ t('attendance.present') }}</span>
                                    <span class="font-bold text-state-success text-sm">{{ gs.summary.attendance.present }}</span>
                                </div>
                                <div class="p-2 rounded-xl bg-cafe-50 dark:bg-surface-dark-2 border border-cafe-200/60 dark:border-cafe-700/60">
                                    <span class="text-cafe-400 block text-[10px] uppercase font-semibold">{{ t('attendance.absent') }}</span>
                                    <span class="font-bold text-state-danger text-sm">{{ gs.summary.attendance.absent }}</span>
                                </div>
                                <div class="p-2 rounded-xl bg-cafe-50 dark:bg-surface-dark-2 border border-cafe-200/60 dark:border-cafe-700/60">
                                    <span class="text-cafe-400 block text-[10px] uppercase font-semibold">{{ t('attendance.justified') }}</span>
                                    <span class="font-bold text-state-warning text-sm">{{ gs.summary.attendance.justified }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Pedagogical Observations -->
            <div v-else-if="activeTab === 'observations'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-serif text-lg font-bold text-cafe-900 dark:text-cafe-100">
                            {{ t('observations.title') }}
                        </h2>
                        <p class="text-xs text-cafe-500 dark:text-cafe-400">
                            {{ observations.length }} {{ t('students.observations_count').toLowerCase() }}
                        </p>
                    </div>

                    <CpButton type="button" class="inline-flex items-center gap-1.5 text-xs shadow-sm" @click="showObsModal = true">
                        <AddCircleRegular class="w-4 h-4" />
                        <span>{{ t('observations.add') }}</span>
                    </CpButton>
                </div>

                <!-- Observations List -->
                <div v-if="observations.length === 0" class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 py-12 text-center space-y-3 shadow-sm">
                    <NotebookRegular class="w-8 h-8 mx-auto text-cafe-400" />
                    <p class="text-xs text-cafe-500 dark:text-cafe-400">{{ t('observations.empty') }}</p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="obs in observations"
                        :key="obs.id"
                        class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 p-5 shadow-xs space-y-2.5 transition-all"
                    >
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-xs font-bold px-2.5 py-0.5 rounded-lg"
                                    :class="{
                                        'bg-amber-100 dark:bg-amber-950/60 text-amber-900 dark:text-amber-200 border border-amber-300/80 dark:border-amber-700': obs.type === 'achievement',
                                        'bg-state-warning/15 text-state-warning': obs.type === 'behavior',
                                        'bg-cafe-200 dark:bg-surface-dark-3 text-cafe-700 dark:text-cafe-300': obs.type === 'performance',
                                        'bg-state-info/15 text-state-info': obs.type === 'followup',
                                    }"
                                >
                                    {{ t('observations.type_' + obs.type) }}
                                </span>
                                <span v-if="obs.group" class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">
                                    {{ obs.group.name }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="text-xs font-medium transition-colors"
                                    :class="obs.status === 'resolved' ? 'text-state-success font-bold' : 'text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-200'"
                                    @click="toggleResolved(obs)"
                                >
                                    {{ obs.status === 'resolved' ? '✓ ' + t('observations.resolved') : t('observations.mark_resolved') }}
                                </button>
                                <button
                                    type="button"
                                    class="text-cafe-400 hover:text-state-danger transition-colors p-1"
                                    :title="t('common.delete')"
                                    @click="deleteObservation(obs)"
                                >
                                    <Delete2Regular class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <p class="text-xs text-cafe-800 dark:text-cafe-200 leading-relaxed whitespace-pre-line">
                            {{ obs.content }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Assigned Groups -->
            <div v-else-if="activeTab === 'groups'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-serif text-lg font-bold text-cafe-900 dark:text-cafe-100">
                            {{ t('students.assigned_groups') }}
                        </h2>
                        <p class="text-xs text-cafe-500 dark:text-cafe-400">
                            {{ student.groups?.length || 0 }} {{ t('students.groups_count') }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-cafe-100 hover:bg-accent-100 dark:bg-surface-dark-2 dark:hover:bg-surface-dark-3 text-cafe-700 dark:text-cafe-200 hover:text-accent-800 dark:hover:text-accent-300 border border-cafe-200 dark:border-cafe-700 transition-colors"
                        @click="openEditModal"
                    >
                        <Edit2Regular class="w-3.5 h-3.5" />
                        <span>{{ t('students.manage_groups') }}</span>
                    </button>
                </div>

                <div v-if="student.groups?.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                    <div
                        v-for="grp in student.groups"
                        :key="grp.id"
                        class="p-5 rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 shadow-xs space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-sm text-cafe-900 dark:text-cafe-100">
                                {{ grp.name }}
                            </h3>
                            <span v-if="grp.period?.is_active" class="px-2 py-0.5 rounded-lg text-[10px] bg-state-success/15 text-state-success font-bold">
                                {{ t('common.active') }}
                            </span>
                        </div>
                        <p v-if="grp.period" class="text-xs text-cafe-500 dark:text-cafe-400">
                            {{ grp.period.name }}
                        </p>
                        <div class="pt-2">
                            <Link
                                :href="route('groups.show', grp.slug || grp.id)"
                                class="text-xs font-semibold text-accent-600 dark:text-accent-400 hover:underline"
                            >
                                {{ t('common.view') }} →
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="text-xs text-cafe-500 py-8 text-center">
                    {{ t('students.no_groups') }}
                </div>
            </div>

            <!-- Modal: Add Pedagogical Observation -->
            <div
                v-if="showObsModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-cafe-900/60 backdrop-blur-xs"
                @click.self="showObsModal = false"
            >
                <div class="bg-white dark:bg-surface-dark-1 rounded-2xl border border-cafe-200 dark:border-cafe-700 p-6 max-w-lg w-full shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-cafe-100 dark:border-cafe-800 pb-3">
                        <h3 class="font-serif text-lg font-bold text-cafe-900 dark:text-cafe-100">
                            {{ t('observations.add') }}
                        </h3>
                        <button
                            type="button"
                            class="text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-200"
                            @click="showObsModal = false"
                        >
                            <CloseCircleRegular class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitObservation" class="space-y-4">
                        <CpSelect
                            id="obs_group"
                            v-model="obsForm.group_id"
                            :label="t('groups.name')"
                            :options="availableGroupOptions"
                            :placeholder="t('observations.select_group')"
                            :error="obsForm.errors.group_id"
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

                        <div>
                            <label for="obs_content" class="cp-label block text-xs font-medium text-cafe-700 dark:text-cafe-200 mb-1">
                                {{ t('observations.content') }}
                            </label>
                            <textarea
                                id="obs_content"
                                v-model="obsForm.content"
                                rows="3"
                                class="w-full px-3 py-2 text-sm bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500"
                                required
                            />
                            <p v-if="obsForm.errors.content" class="cp-error text-xs mt-1">{{ obsForm.errors.content }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <CpButton type="button" variant="ghost" @click="showObsModal = false">
                                {{ t('common.cancel') }}
                            </CpButton>
                            <CpButton type="submit" :disabled="obsForm.processing">
                                {{ t('observations.submit') }}
                            </CpButton>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal: Edit Profile, Contact & Groups -->
            <div
                v-if="showEditProfileModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-cafe-900/60 backdrop-blur-xs"
                @click.self="showEditProfileModal = false"
            >
                <div class="bg-white dark:bg-surface-dark-1 rounded-2xl border border-cafe-200 dark:border-cafe-700 p-6 max-w-xl w-full shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between border-b border-cafe-100 dark:border-cafe-800 pb-3">
                        <div class="flex items-center gap-3">
                            <StudentAvatar :student="editForm" size="sm" />
                            <h3 class="font-serif text-lg font-bold text-cafe-900 dark:text-cafe-100">
                                {{ t('students.edit_profile') }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-200"
                            @click="showEditProfileModal = false"
                        >
                            <CloseCircleRegular class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitEditProfile" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <CpInput
                                id="edit_first_name"
                                v-model="editForm.first_name"
                                :label="t('field.first_name')"
                                :error="editForm.errors.first_name"
                                required
                            />
                            <CpInput
                                id="edit_last_name"
                                v-model="editForm.last_name"
                                :label="t('field.last_name')"
                                :error="editForm.errors.last_name"
                                required
                            />
                        </div>

                        <div>
                            <label for="edit_guardian" class="cp-label block text-xs font-medium text-cafe-700 dark:text-cafe-200 mb-1">
                                {{ t('students.guardian_name') }}
                            </label>
                            <input
                                id="edit_guardian"
                                v-model="editForm.guardian_name"
                                type="text"
                                :placeholder="t('students.guardian_placeholder')"
                                class="w-full px-3 py-2 text-sm bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="edit_email" class="cp-label block text-xs font-medium text-cafe-700 dark:text-cafe-200 mb-1">
                                    {{ t('students.email') }}
                                </label>
                                <input
                                    id="edit_email"
                                    v-model="editForm.email"
                                    type="email"
                                    :placeholder="t('students.email_placeholder')"
                                    class="w-full px-3 py-2 text-sm bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500"
                                />
                            </div>

                            <div>
                                <label for="edit_phone" class="cp-label block text-xs font-medium text-cafe-700 dark:text-cafe-200 mb-1">
                                    {{ t('students.phone') }}
                                </label>
                                <input
                                    id="edit_phone"
                                    v-model="editForm.phone"
                                    type="text"
                                    :placeholder="t('students.phone_placeholder')"
                                    class="w-full px-3 py-2 text-sm bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label for="edit_notes" class="cp-label block text-xs font-medium text-cafe-700 dark:text-cafe-200 mb-1">
                                {{ t('students.notes') }}
                            </label>
                            <textarea
                                id="edit_notes"
                                v-model="editForm.notes"
                                rows="3"
                                :placeholder="t('students.notes_placeholder')"
                                class="w-full px-3 py-2 text-sm bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500"
                            />
                        </div>

                        <!-- Groups Assignment Checkboxes -->
                        <div v-if="allGroups?.length" class="space-y-2">
                            <label class="cp-label block text-xs font-medium text-cafe-700 dark:text-cafe-200">
                                {{ t('students.assign_groups') }}
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-40 overflow-y-auto p-1">
                                <div
                                    v-for="grp in allGroups"
                                    :key="grp.id"
                                    :class="[
                                        'p-2.5 rounded-xl border cursor-pointer transition-all flex items-center justify-between text-xs',
                                        editForm.group_ids.includes(grp.id)
                                            ? 'bg-accent-50/70 dark:bg-accent-950/30 border-accent-400 dark:border-accent-700/60 font-semibold'
                                            : 'bg-cafe-50/40 dark:bg-surface-dark-2 border-cafe-200/80 dark:border-cafe-700',
                                    ]"
                                    @click="toggleEditGroup(grp.id)"
                                >
                                    <span class="truncate">{{ grp.name }}</span>
                                    <CheckCircleRegular v-if="editForm.group_ids.includes(grp.id)" class="w-4 h-4 text-accent-600 flex-shrink-0" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-cafe-100 dark:border-cafe-800">
                            <CpButton type="button" variant="ghost" @click="showEditProfileModal = false">
                                {{ t('common.cancel') }}
                            </CpButton>
                            <CpButton type="submit" :disabled="editForm.processing">
                                {{ t('students.update_profile') }}
                            </CpButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
