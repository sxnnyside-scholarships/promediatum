<script setup lang="ts">
/**
 * Observations Index — Pedagogical Incident & Follow-Up Workspace
 *
 * Designed under the Café Pedagógico Design System:
 * rounded-2xl geometry, restful gradient header card, MingCute icons,
 * real-time multi-criteria filtering, smart priority sorting, student initials avatars,
 * and direct one-click observation entry modal.
 */

import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AddCircleRegular,
    AlertRegular,
    AwardRegular,
    CalendarMonthRegular,
    ChartLineRegular,
    CheckCircleRegular,
    CloseCircleRegular,
    Delete2Regular,
    GroupRegular,
    NotebookRegular,
    Search2Regular,
    SortAscendingRegular,
    TimeRegular,
    User4Regular,
} from '@mingcute/vue/core-regular';
import { computed, ref, watch } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface StudentInfo {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    slug: string;
    initials: string;
}

interface GroupInfo {
    id: number;
    name: string;
    slug?: string;
    period?: {
        id: number;
        name: string;
    };
    students?: StudentInfo[];
}

interface ObservationItem {
    id: number;
    student_id: number;
    group_id: number;
    type: 'performance' | 'behavior' | 'achievement' | 'followup';
    content: string;
    status: 'pending' | 'resolved';
    resolved_at: string | null;
    created_at: string;
    student?: StudentInfo;
    group?: GroupInfo;
}

const props = defineProps<{
    observations: ObservationItem[];
    groups?: GroupInfo[];
    students?: StudentInfo[];
    filters?: {
        status?: string;
        type?: string;
        group_id?: string;
    };
}>();

const { t } = useTranslations();

// Filters state
const searchQuery = ref('');
const statusTab = ref<'all' | 'pending' | 'resolved'>(
    (props.filters?.status as 'pending' | 'resolved') || 'all',
);
const typeFilter = ref<string>(props.filters?.type || 'all');
const groupFilter = ref<string>(props.filters?.group_id || 'all');
const sortBy = ref<'smart' | 'recent' | 'oldest' | 'student'>('smart');

// Summary counts
const totalCount = computed(() => props.observations.length);
const pendingCount = computed(
    () => props.observations.filter((o) => o.status === 'pending').length,
);
const resolvedCount = computed(
    () => props.observations.filter((o) => o.status === 'resolved').length,
);
const achievementCount = computed(
    () => props.observations.filter((o) => o.type === 'achievement').length,
);

// Smart filtered & sorted list
const filteredObservations = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    const st = statusTab.value;
    const tp = typeFilter.value;
    const grp = groupFilter.value;

    let list = props.observations.filter((obs) => {
        // Text search
        if (q) {
            const matchesStudent = obs.student?.full_name?.toLowerCase().includes(q);
            const matchesContent = obs.content?.toLowerCase().includes(q);
            const matchesGroup = obs.group?.name?.toLowerCase().includes(q);
            if (!matchesStudent && !matchesContent && !matchesGroup) {
                return false;
            }
        }

        // Status tab
        if (st !== 'all' && obs.status !== st) {
            return false;
        }

        // Type filter
        if (tp !== 'all' && obs.type !== tp) {
            return false;
        }

        // Group filter
        if (grp !== 'all' && String(obs.group_id) !== grp) {
            return false;
        }

        return true;
    });

    // Sorting
    return list.sort((a, b) => {
        if (sortBy.value === 'smart') {
            // Pending items first, then by created_at desc
            if (a.status !== b.status) {
                return a.status === 'pending' ? -1 : 1;
            }
            return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        }
        if (sortBy.value === 'recent') {
            return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        }
        if (sortBy.value === 'oldest') {
            return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
        }
        if (sortBy.value === 'student') {
            const nameA = a.student?.full_name || '';
            const nameB = b.student?.full_name || '';
            return nameA.localeCompare(nameB);
        }
        return 0;
    });
});

function toggleResolved(obs: ObservationItem) {
    router.post(route('observations.toggle-resolved', obs.id), {}, { preserveScroll: true });
}

function deleteObs(obs: ObservationItem) {
    if (confirm(t('observations.delete_confirm'))) {
        router.delete(route('observations.destroy', obs.id), { preserveScroll: true });
    }
}

function formatDate(dateStr: string | null) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

// Quick Create Modal State
const showCreateModal = ref(false);
const newObsForm = useForm({
    group_id: '',
    student_id: '',
    type: 'performance' as 'performance' | 'behavior' | 'achievement' | 'followup',
    content: '',
});

// Dynamic students list based on selected group in creation modal
const selectableStudents = computed(() => {
    if (!newObsForm.group_id) {
        return props.students || [];
    }
    const grp = (props.groups || []).find((g) => String(g.id) === String(newObsForm.group_id));
    if (grp?.students?.length) {
        return grp.students;
    }
    return props.students || [];
});

const selectedStudentPreview = computed(() => {
    if (!newObsForm.student_id) return null;
    return (
        (props.students || []).find((s) => String(s.id) === String(newObsForm.student_id)) || null
    );
});

// Watch group change to reset student if not in group
watch(
    () => newObsForm.group_id,
    () => {
        if (newObsForm.student_id) {
            const isStillAvailable = selectableStudents.value.some(
                (s) => String(s.id) === String(newObsForm.student_id),
            );
            if (!isStillAvailable) {
                newObsForm.student_id = '';
            }
        }
    },
);

function submitNewObservation() {
    newObsForm.post(route('observations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            newObsForm.reset('group_id', 'student_id', 'content');
            newObsForm.type = 'performance';
        },
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('observations.title')" />

        <div class="space-y-6 max-w-7xl">
            <!-- Header card with restful pedagogical gradient -->
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                            <NotebookRegular class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                                {{ t('observations.title') }}
                            </h1>
                            <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5 leading-relaxed">
                                {{ t('observations.subtitle') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <Link :href="route('exports.history')">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-cafe-100 hover:bg-accent-100 dark:bg-surface-dark-2 dark:hover:bg-surface-dark-3 text-cafe-700 dark:text-cafe-200 hover:text-accent-800 dark:hover:text-accent-300 border border-cafe-200 dark:border-cafe-700 transition-colors"
                            >
                                <span>{{ t('exports.title') }}</span>
                            </button>
                        </Link>

                        <CpButton
                            type="button"
                            class="inline-flex items-center gap-2 text-sm shadow-sm"
                            @click="showCreateModal = true"
                        >
                            <AddCircleRegular class="w-4 h-4" />
                            <span>{{ t('observations.add') }}</span>
                        </CpButton>
                    </div>
                </div>

                <!-- Metric Quick Highlights -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-5 border-t border-cafe-200/60 dark:border-cafe-800/60">
                    <div class="rounded-xl bg-white/80 dark:bg-surface-dark-3 p-3.5 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">{{ t('observations.stat_total') }}</p>
                            <NotebookRegular class="w-4 h-4 text-cafe-400" />
                        </div>
                        <p class="text-xl font-bold font-serif text-cafe-900 dark:text-cafe-100 mt-1">
                            {{ totalCount }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-white/80 dark:bg-surface-dark-3 p-3.5 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">{{ t('observations.stat_pending') }}</p>
                            <TimeRegular class="w-4 h-4 text-state-warning" />
                        </div>
                        <p class="text-xl font-bold font-serif text-state-warning mt-1">
                            {{ pendingCount }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-white/80 dark:bg-surface-dark-3 p-3.5 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">{{ t('observations.stat_resolved') }}</p>
                            <CheckCircleRegular class="w-4 h-4 text-state-success" />
                        </div>
                        <p class="text-xl font-bold font-serif text-state-success mt-1">
                            {{ resolvedCount }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-white/80 dark:bg-surface-dark-3 p-3.5 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">{{ t('observations.stat_achievements') }}</p>
                            <AwardRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                        </div>
                        <p class="text-xl font-bold font-serif text-accent-600 dark:text-accent-400 mt-1">
                            {{ achievementCount }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Controls: Status Tabs, Search, Filters, Smart Sort -->
            <div class="bg-white dark:bg-surface-dark-1 rounded-2xl border border-cafe-200 dark:border-cafe-800 p-4 shadow-sm space-y-3">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                    <!-- Status Filter Tabs -->
                    <div class="flex items-center gap-1 border border-cafe-200 dark:border-cafe-700 rounded-lg p-0.5 bg-cafe-100/70 dark:bg-surface-dark-2">
                        <button
                            type="button"
                            :class="[
                                'px-3 py-1.5 rounded-md text-xs font-medium transition-colors flex items-center gap-1.5',
                                statusTab === 'all'
                                    ? 'bg-white dark:bg-surface-dark-3 text-accent-700 dark:text-accent-300 font-bold shadow-2xs'
                                    : 'text-cafe-600 dark:text-cafe-400 hover:text-cafe-900 dark:hover:text-cafe-200',
                            ]"
                            @click="statusTab = 'all'"
                        >
                            <span>{{ t('observations.all_statuses') }}</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-cafe-200 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300">
                                {{ totalCount }}
                            </span>
                        </button>

                        <button
                            type="button"
                            :class="[
                                'px-3 py-1.5 rounded-md text-xs font-medium transition-colors flex items-center gap-1.5',
                                statusTab === 'pending'
                                    ? 'bg-white dark:bg-surface-dark-3 text-state-warning font-bold shadow-2xs'
                                    : 'text-cafe-600 dark:text-cafe-400 hover:text-cafe-900 dark:hover:text-cafe-200',
                            ]"
                            @click="statusTab = 'pending'"
                        >
                            <span>{{ t('observations.pending') }}</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-state-warning/20 text-state-warning font-bold">
                                {{ pendingCount }}
                            </span>
                        </button>

                        <button
                            type="button"
                            :class="[
                                'px-3 py-1.5 rounded-md text-xs font-medium transition-colors flex items-center gap-1.5',
                                statusTab === 'resolved'
                                    ? 'bg-white dark:bg-surface-dark-3 text-state-success font-bold shadow-2xs'
                                    : 'text-cafe-600 dark:text-cafe-400 hover:text-cafe-900 dark:hover:text-cafe-200',
                            ]"
                            @click="statusTab = 'resolved'"
                        >
                            <span>{{ t('observations.resolved') }}</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-state-success/20 text-state-success font-bold">
                                {{ resolvedCount }}
                            </span>
                        </button>
                    </div>

                    <!-- Smart Sorting Dropdown -->
                    <div class="flex items-center gap-2 self-end lg:self-auto">
                        <span class="text-xs text-cafe-500 font-medium flex items-center gap-1">
                            <SortAscendingRegular class="w-3.5 h-3.5 text-accent-600 dark:text-accent-400" />
                            <span>{{ t('observations.sort_by') }}:</span>
                        </span>
                        <select
                            v-model="sortBy"
                            class="px-3 py-1.5 text-xs bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-800 dark:text-cafe-200 focus:outline-none focus:ring-1 focus:ring-accent-500 font-medium"
                        >
                            <option value="smart">{{ t('observations.sort_smart') }}</option>
                            <option value="recent">{{ t('observations.sort_recent') }}</option>
                            <option value="oldest">{{ t('observations.sort_oldest') }}</option>
                            <option value="student">{{ t('observations.sort_student') }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2 border-t border-cafe-100 dark:border-cafe-800">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[220px]">
                        <Search2Regular class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-cafe-400 pointer-events-none" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="t('observations.search_placeholder')"
                            class="w-full pl-9 pr-3 py-2 text-xs bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500 transition-colors"
                        />
                    </div>

                    <!-- Type Filter -->
                    <div class="min-w-[170px]">
                        <select
                            v-model="typeFilter"
                            class="w-full px-3 py-2 text-xs bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-800 dark:text-cafe-200 focus:outline-none focus:ring-1 focus:ring-accent-500 transition-colors"
                        >
                            <option value="all">{{ t('observations.all_types') }}</option>
                            <option value="performance">{{ t('observations.type_performance') }}</option>
                            <option value="behavior">{{ t('observations.type_behavior') }}</option>
                            <option value="achievement">{{ t('observations.type_achievement') }}</option>
                            <option value="followup">{{ t('observations.type_followup') }}</option>
                        </select>
                    </div>

                    <!-- Group Filter -->
                    <div v-if="groups?.length" class="min-w-[180px]">
                        <select
                            v-model="groupFilter"
                            class="w-full px-3 py-2 text-xs bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-800 dark:text-cafe-200 focus:outline-none focus:ring-1 focus:ring-accent-500 transition-colors"
                        >
                            <option value="all">{{ t('observations.all_groups') }}</option>
                            <option v-for="grp in groups" :key="grp.id" :value="String(grp.id)">
                                {{ grp.name }} {{ grp.period ? `(${grp.period.name})` : '' }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Empty State: 0 total observations -->
            <div
                v-if="observations.length === 0"
                class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 py-16 text-center space-y-4 shadow-sm"
            >
                <div class="w-16 h-16 mx-auto rounded-2xl bg-cafe-100 dark:bg-surface-dark-2 flex items-center justify-center text-cafe-500 dark:text-cafe-400 border border-cafe-200/60 dark:border-cafe-700">
                    <NotebookRegular class="w-8 h-8" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-serif text-lg text-cafe-900 dark:text-cafe-100 font-bold">
                        {{ t('observations.empty') }}
                    </h3>
                    <p class="text-xs text-cafe-500 dark:text-cafe-400 max-w-sm mx-auto">
                        {{ t('observations.subtitle') }}
                    </p>
                </div>
                <CpButton
                    type="button"
                    class="inline-flex items-center gap-2 text-sm shadow-sm"
                    @click="showCreateModal = true"
                >
                    <AddCircleRegular class="w-4 h-4" />
                    <span>{{ t('observations.add') }}</span>
                </CpButton>
            </div>

            <!-- Empty State: Filter mismatch -->
            <div
                v-else-if="filteredObservations.length === 0"
                class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 py-12 text-center space-y-3 shadow-sm"
            >
                <p class="text-xs text-cafe-600 dark:text-cafe-300 font-medium">
                    {{ t('observations.no_results_filtered') }}
                </p>
                <button
                    type="button"
                    class="text-xs text-accent-600 dark:text-accent-400 hover:underline font-semibold"
                    @click="searchQuery = ''; statusTab = 'all'; typeFilter = 'all'; groupFilter = 'all'; sortBy = 'smart'"
                >
                    {{ t('common.reset_filters') }}
                </button>
            </div>

            <!-- Observations Cards List -->
            <div v-else class="space-y-4">
                <div
                    v-for="obs in filteredObservations"
                    :key="obs.id"
                    :class="[
                        'rounded-2xl border p-5 sm:p-6 shadow-xs hover:shadow-md transition-all space-y-3',
                        obs.status === 'resolved'
                            ? 'bg-white/80 dark:bg-surface-dark-1/80 border-cafe-200/80 dark:border-cafe-800 opacity-90'
                            : 'bg-white dark:bg-surface-dark-1 border-cafe-200 dark:border-cafe-800',
                    ]"
                >
                    <!-- Header of card: Student avatar, name, group, type, and quick actions -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-cafe-100 dark:border-cafe-800/60 pb-3">
                        <div class="flex items-center gap-3 flex-wrap">
                            <!-- Student initials avatar -->
                            <StudentAvatar :student="obs.student" size="sm" />

                            <Link
                                v-if="obs.student"
                                :href="route('students.show', obs.student.slug)"
                                class="font-serif font-bold text-base text-cafe-900 dark:text-cafe-100 hover:text-accent-600 dark:hover:text-accent-400 transition-colors"
                            >
                                {{ obs.student.full_name }}
                            </Link>

                            <!-- Group badge -->
                            <span
                                v-if="obs.group"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs bg-cafe-100 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300 font-medium"
                            >
                                <GroupRegular class="w-3.5 h-3.5 text-cafe-400" />
                                <span>{{ obs.group.name }}</span>
                            </span>

                            <!-- Type badge with specific icon -->
                            <span
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-bold"
                                :class="{
                                    'bg-accent-100 dark:bg-accent-950/60 text-accent-800 dark:text-accent-200 border border-accent-200/70 dark:border-accent-800/60': obs.type === 'achievement',
                                    'bg-state-warning/15 text-state-warning border border-state-warning/30': obs.type === 'behavior',
                                    'bg-cafe-200/80 dark:bg-surface-dark-3 text-cafe-800 dark:text-cafe-200 border border-cafe-300 dark:border-cafe-700': obs.type === 'performance',
                                    'bg-state-info/15 text-state-info border border-state-info/30': obs.type === 'followup',
                                }"
                            >
                                <AwardRegular v-if="obs.type === 'achievement'" class="w-3.5 h-3.5" />
                                <AlertRegular v-else-if="obs.type === 'behavior'" class="w-3.5 h-3.5" />
                                <ChartLineRegular v-else-if="obs.type === 'performance'" class="w-3.5 h-3.5" />
                                <TimeRegular v-else class="w-3.5 h-3.5" />
                                <span>{{ t('observations.type_' + obs.type) }}</span>
                            </span>

                            <!-- Created date -->
                            <span class="inline-flex items-center gap-1 text-[11px] text-cafe-400 dark:text-cafe-500">
                                <CalendarMonthRegular class="w-3.5 h-3.5" />
                                <span>{{ formatDate(obs.created_at) }}</span>
                            </span>
                        </div>

                        <!-- Action controls -->
                        <div class="flex items-center gap-2.5 self-end sm:self-auto">
                            <!-- Toggle resolved button -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold transition-colors"
                                :class="obs.status === 'resolved'
                                    ? 'bg-state-success/15 text-state-success border border-state-success/30 hover:bg-state-success/20'
                                    : 'bg-cafe-100 hover:bg-cafe-200 dark:bg-surface-dark-2 dark:hover:bg-surface-dark-3 text-cafe-600 dark:text-cafe-300 border border-cafe-200 dark:border-cafe-700'"
                                :title="obs.status === 'resolved' ? t('observations.mark_pending') : t('observations.mark_resolved')"
                                @click="toggleResolved(obs)"
                            >
                                <CheckCircleRegular class="w-3.5 h-3.5" />
                                <span>{{ obs.status === 'resolved' ? t('observations.resolved') : t('observations.mark_resolved') }}</span>
                            </button>

                            <!-- Delete button -->
                            <button
                                type="button"
                                class="p-1.5 rounded-lg text-cafe-400 hover:text-state-danger hover:bg-state-danger/10 transition-colors"
                                :title="t('common.delete')"
                                @click="deleteObs(obs)"
                            >
                                <Delete2Regular class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Observation content -->
                    <p class="text-sm text-cafe-800 dark:text-cafe-200 leading-relaxed whitespace-pre-line pl-0.5">
                        {{ obs.content }}
                    </p>

                    <!-- Resolved timestamp banner if resolved -->
                    <div
                        v-if="obs.status === 'resolved' && obs.resolved_at"
                        class="pt-1 text-[11px] text-state-success flex items-center gap-1.5 font-medium"
                    >
                        <CheckCircleRegular class="w-3.5 h-3.5" />
                        <span>{{ t('observations.resolved') }}: {{ formatDate(obs.resolved_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Modal: Nueva Observación -->
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-cafe-900/60 backdrop-blur-xs"
                @click.self="showCreateModal = false"
            >
                <div class="bg-white dark:bg-surface-dark-1 rounded-2xl border border-cafe-200 dark:border-cafe-700 p-6 max-w-xl w-full shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between border-b border-cafe-100 dark:border-cafe-800 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50">
                                <NotebookRegular class="w-5 h-5" />
                            </div>
                            <h3 class="font-serif text-lg font-bold text-cafe-900 dark:text-cafe-100">
                                {{ t('observations.new_observation') }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-200"
                            @click="showCreateModal = false"
                        >
                            <CloseCircleRegular class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitNewObservation" class="space-y-4">
                        <!-- Group Selection -->
                        <div>
                            <label for="new_obs_group" class="cp-label block text-xs font-bold text-cafe-700 dark:text-cafe-200 mb-1">
                                {{ t('groups.name') }}
                            </label>
                            <select
                                id="new_obs_group"
                                v-model="newObsForm.group_id"
                                class="w-full px-3 py-2 text-xs bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 focus:outline-none focus:ring-1 focus:ring-accent-500 font-medium"
                                required
                            >
                                <option value="" disabled>{{ t('observations.select_group') }}</option>
                                <option v-for="g in groups" :key="g.id" :value="String(g.id)">
                                    {{ g.name }} {{ g.period ? `(${g.period.name})` : '' }}
                                </option>
                            </select>
                            <p v-if="newObsForm.errors.group_id" class="cp-error text-xs mt-1">{{ newObsForm.errors.group_id }}</p>
                        </div>

                        <!-- Student Selection with live Avatar Preview -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="new_obs_student" class="cp-label block text-xs font-bold text-cafe-700 dark:text-cafe-200">
                                    {{ t('students.name') }}
                                </label>
                                <div v-if="selectedStudentPreview" class="flex items-center gap-1.5">
                                    <StudentAvatar :student="selectedStudentPreview" size="xs" />
                                    <span class="text-xs text-cafe-600 dark:text-cafe-300 font-medium">{{ selectedStudentPreview.full_name }}</span>
                                </div>
                            </div>
                            <select
                                id="new_obs_student"
                                v-model="newObsForm.student_id"
                                class="w-full px-3 py-2 text-xs bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 focus:outline-none focus:ring-1 focus:ring-accent-500 font-medium"
                                required
                            >
                                <option value="" disabled>{{ t('observations.select_student') }}</option>
                                <option v-for="st in selectableStudents" :key="st.id" :value="String(st.id)">
                                    {{ st.full_name }}
                                </option>
                            </select>
                            <p v-if="newObsForm.errors.student_id" class="cp-error text-xs mt-1">{{ newObsForm.errors.student_id }}</p>
                        </div>

                        <!-- Type Selector Pills -->
                        <div>
                            <label class="cp-label block text-xs font-bold text-cafe-700 dark:text-cafe-200 mb-1.5">
                                {{ t('observations.type') }}
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <button
                                    type="button"
                                    :class="[
                                        'p-2.5 rounded-xl border text-xs font-medium flex items-center justify-center gap-1.5 transition-all',
                                        newObsForm.type === 'performance'
                                            ? 'bg-cafe-200 dark:bg-surface-dark-3 border-cafe-400 font-bold text-cafe-900 dark:text-cafe-100 shadow-2xs'
                                            : 'bg-cafe-50/60 dark:bg-surface-dark-2 border-cafe-200 dark:border-cafe-700 text-cafe-600 dark:text-cafe-400',
                                    ]"
                                    @click="newObsForm.type = 'performance'"
                                >
                                    <ChartLineRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('observations.type_performance') }}</span>
                                </button>

                                <button
                                    type="button"
                                    :class="[
                                        'p-2.5 rounded-xl border text-xs font-medium flex items-center justify-center gap-1.5 transition-all',
                                        newObsForm.type === 'behavior'
                                            ? 'bg-state-warning/20 border-state-warning text-state-warning font-bold shadow-2xs'
                                            : 'bg-cafe-50/60 dark:bg-surface-dark-2 border-cafe-200 dark:border-cafe-700 text-cafe-600 dark:text-cafe-400',
                                    ]"
                                    @click="newObsForm.type = 'behavior'"
                                >
                                    <AlertRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('observations.type_behavior') }}</span>
                                </button>

                                <button
                                    type="button"
                                    :class="[
                                        'p-2.5 rounded-xl border text-xs font-medium flex items-center justify-center gap-1.5 transition-all',
                                        newObsForm.type === 'achievement'
                                            ? 'bg-accent-100 dark:bg-accent-950/60 border-accent-500 text-accent-800 dark:text-accent-200 font-bold shadow-2xs'
                                            : 'bg-cafe-50/60 dark:bg-surface-dark-2 border-cafe-200 dark:border-cafe-700 text-cafe-600 dark:text-cafe-400',
                                    ]"
                                    @click="newObsForm.type = 'achievement'"
                                >
                                    <AwardRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('observations.type_achievement') }}</span>
                                </button>

                                <button
                                    type="button"
                                    :class="[
                                        'p-2.5 rounded-xl border text-xs font-medium flex items-center justify-center gap-1.5 transition-all',
                                        newObsForm.type === 'followup'
                                            ? 'bg-state-info/20 border-state-info text-state-info font-bold shadow-2xs'
                                            : 'bg-cafe-50/60 dark:bg-surface-dark-2 border-cafe-200 dark:border-cafe-700 text-cafe-600 dark:text-cafe-400',
                                    ]"
                                    @click="newObsForm.type = 'followup'"
                                >
                                    <TimeRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('observations.type_followup') }}</span>
                                </button>
                            </div>
                            <p v-if="newObsForm.errors.type" class="cp-error text-xs mt-1">{{ newObsForm.errors.type }}</p>
                        </div>

                        <!-- Content Textarea -->
                        <div>
                            <label for="new_obs_content" class="cp-label block text-xs font-bold text-cafe-700 dark:text-cafe-200 mb-1">
                                {{ t('observations.content') }}
                            </label>
                            <textarea
                                id="new_obs_content"
                                v-model="newObsForm.content"
                                rows="4"
                                :placeholder="t('observations.content_placeholder')"
                                class="w-full px-3 py-2 text-xs bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500"
                                required
                            />
                            <p v-if="newObsForm.errors.content" class="cp-error text-xs mt-1">{{ newObsForm.errors.content }}</p>
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-cafe-100 dark:border-cafe-800">
                            <CpButton type="button" variant="ghost" @click="showCreateModal = false">
                                {{ t('common.cancel') }}
                            </CpButton>
                            <CpButton type="submit" :disabled="newObsForm.processing" class="inline-flex items-center gap-2 text-xs shadow-sm">
                                <AddCircleRegular class="w-4 h-4" />
                                <span>{{ t('observations.submit') }}</span>
                            </CpButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
