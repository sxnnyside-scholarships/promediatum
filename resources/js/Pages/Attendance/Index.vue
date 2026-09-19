<script setup lang="ts">
/**
 * Attendance Index — /groups/{group}/attendance
 * Ergonomic daily roll-call marking and comprehensive group attendance matrix.
 */

import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertRegular,
    CalendarMonthRegular,
    CheckCircleRegular,
    CheckRegular,
    GroupRegular,
    LeftSmallRegular,
    RightSmallRegular,
    Search2Regular,
    SparklesRegular,
    Task2Regular,
    TimeRegular,
    User4Regular,
} from '@mingcute/vue/core-regular';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import { useToast } from '@/composables/useToast';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Group } from '@/types';

type AttendanceStatus = 'present' | 'absent' | 'justified';

interface StudentData {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    initials: string;
    slug: string;
    status: AttendanceStatus | null;
    summary: {
        total: number;
        present: number;
        absent: number;
        justified: number;
        rate: number | null;
        absence_streak: number;
        has_absence_alert: boolean;
    };
    matrix: Record<string, AttendanceStatus | null>;
}

interface OtherGroupItem {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    group: Group;
    date: string;
    students: StudentData[];
    sessionDates: string[];
    otherGroups: OtherGroupItem[];
    stats: {
        total: number;
        present: number;
        absent: number;
        justified: number;
        pending: number;
        rate: number;
    };
}

const props = defineProps<Props>();
const { t } = useTranslations();
const toast = useToast();

// ── Tabs ──
const activeTab = ref<'rollcall' | 'matrix'>('rollcall');

// Read url param tab if present
onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('tab') === 'matrix') {
        activeTab.value = 'matrix';
    }
});

// ── Date Management ──
const selectedDate = ref(props.date);
const isNavigatingDate = ref(false);

function changeDate(newDate: string) {
    if (newDate === selectedDate.value) return;
    selectedDate.value = newDate;
    isNavigatingDate.value = true;
    router.get(
        route('attendance.index', props.group.slug ?? props.group.id),
        { date: newDate, tab: activeTab.value },
        {
            preserveState: false,
            preserveScroll: true,
            onFinish: () => (isNavigatingDate.value = false),
        },
    );
}

function stepDate(days: number) {
    const current = new Date(`${selectedDate.value}T12:00:00`);
    current.setDate(current.getDate() + days);
    const yyyy = current.getFullYear();
    const mm = String(current.getMonth() + 1).padStart(2, '0');
    const dd = String(current.getDate()).padStart(2, '0');
    changeDate(`${yyyy}-${mm}-${dd}`);
}

function setToday() {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    changeDate(`${yyyy}-${mm}-${dd}`);
}

// ── Search Filter ──
const searchQuery = ref('');

// ── In-Memory Roll-Call Records ──
const localStatus = ref<Record<number, AttendanceStatus | null>>({});
const isDirty = ref(false);

function initLocalStatus() {
    const map: Record<number, AttendanceStatus | null> = {};
    for (const s of props.students) {
        map[s.id] = s.status || null;
    }
    localStatus.value = map;
    isDirty.value = false;
}

watch(() => props.students, initLocalStatus, { immediate: true });

// Filtered students for daily roll-call
const filteredStudents = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.students;
    return props.students.filter(
        (s) =>
            s.full_name.toLowerCase().includes(q) ||
            s.first_name.toLowerCase().includes(q) ||
            s.last_name.toLowerCase().includes(q),
    );
});

// Real-time live counts
const liveCounts = computed(() => {
    let present = 0;
    let absent = 0;
    let justified = 0;
    let pending = 0;

    for (const s of props.students) {
        const status = localStatus.value[s.id];
        if (status === 'present') present++;
        else if (status === 'absent') absent++;
        else if (status === 'justified') justified++;
        else pending++;
    }

    const total = props.students.length;
    const rate = total > 0 ? Math.round((present / total) * 100) : 0;

    return { total, present, absent, justified, pending, rate };
});

function setStudentStatus(studentId: number, status: AttendanceStatus) {
    localStatus.value[studentId] = status;
    isDirty.value = true;
}

function setAll(status: AttendanceStatus) {
    for (const s of props.students) {
        localStatus.value[s.id] = status;
    }
    isDirty.value = true;
}

function resetAll() {
    for (const s of props.students) {
        localStatus.value[s.id] = null;
    }
    isDirty.value = true;
}

// ── Save Functionality ──
const isSaving = ref(false);

function saveAttendance() {
    isSaving.value = true;
    const records = props.students
        .filter((s) => localStatus.value[s.id] !== null)
        .map((s) => ({
            student_id: s.id,
            status: localStatus.value[s.id] as AttendanceStatus,
        }));

    router.post(
        route('attendance.store', props.group.slug ?? props.group.id),
        {
            date: selectedDate.value,
            records,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isDirty.value = false;
                toast.success(t('attendance.saved_successfully'));
            },
            onError: () => toast.error(t('attendance.save_error')),
            onFinish: () => (isSaving.value = false),
        },
    );
}

// OS-aware shortcut detection (⌘S on Mac, Ctrl+S on Windows/Linux)
const isMac =
    typeof navigator !== 'undefined' &&
    /Mac|iPod|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
const saveShortcutLabel = computed(() => (isMac ? '⌘S' : 'Ctrl+S'));
const saveShortcutParts = computed(() => {
    const raw = t('attendance.save_shortcut_hint', { shortcut: '___KEY___' });
    return raw.split('___KEY___');
});

// Hotkey: Ctrl/Cmd + S to save
function handleKeyDown(e: KeyboardEvent) {
    if ((e.metaKey || e.ctrlKey) && e.key === 's') {
        e.preventDefault();
        if (isDirty.value && !isSaving.value) {
            saveAttendance();
        }
    }
}

onMounted(() => window.addEventListener('keydown', handleKeyDown));
onUnmounted(() => window.removeEventListener('keydown', handleKeyDown));

function formatDisplayDate(dateStr: string): string {
    if (!dateStr) return '';
    try {
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 12);
            return date.toLocaleDateString(undefined, {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
        }
    } catch {
        // Fallback
    }
    return dateStr;
}

function formatShortDate(dateStr: string): string {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}`;
    }
    return dateStr;
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`${t('attendance.title')} — ${group.name}`" />

        <div class="space-y-6">
            <!-- ═══ 1. CONTEXT HEADER ═══ -->
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm">
                <!-- Navigation path & Group Switcher -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-cafe-200/60 dark:border-surface-dark-3 text-xs">
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('attendance.dashboard')"
                            class="text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 transition-colors"
                        >
                            {{ t('attendance.dashboard') }}
                        </Link>
                        <span class="text-cafe-300 dark:text-cafe-600">/</span>
                        <Link
                            :href="route('groups.show', group.slug)"
                            class="font-semibold text-cafe-800 hover:text-accent-600 dark:text-cafe-100 dark:hover:text-accent-400 transition-colors"
                        >
                            {{ group.name }}
                        </Link>
                    </div>

                    <!-- Quick Group Switcher -->
                    <div v-if="otherGroups.length > 1" class="flex items-center gap-1.5 overflow-x-auto py-0.5">
                        <span class="text-cafe-400 text-[11px] shrink-0">{{ t('attendance.switch_group') }}:</span>
                        <Link
                            v-for="og in otherGroups"
                            :key="og.id"
                            :href="route('attendance.index', og.slug)"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors shrink-0"
                            :class="og.id === group.id
                                ? 'bg-accent-600 text-white font-semibold shadow-2xs'
                                : 'bg-white/80 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300 border border-cafe-200/80 dark:border-cafe-700 hover:bg-cafe-100 dark:hover:bg-surface-dark-3'"
                        >
                            {{ og.name }}
                        </Link>
                    </div>
                </div>

                <!-- Group Info & Tab Switch -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                            <Task2Regular class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                                {{ group.name }}
                            </h1>
                            <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5">
                                <span v-if="group.subject">{{ group.subject }} · </span>
                                <span>{{ group.period?.name }}</span>
                                <span class="mx-1.5 text-cafe-300 dark:text-cafe-600">|</span>
                                <span class="font-medium">{{ students.length }} {{ t('students.title').toLowerCase() }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Primary View Mode Tabs -->
                    <div class="inline-flex p-1 rounded-xl bg-cafe-100 dark:bg-surface-dark-2 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs self-start lg:self-center">
                        <button
                            type="button"
                            @click="activeTab = 'rollcall'"
                            class="px-4 py-2 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-1.5"
                            :class="activeTab === 'rollcall'
                                ? 'bg-white dark:bg-surface-dark-1 text-cafe-900 dark:text-cafe-50 shadow-xs'
                                : 'text-cafe-600 dark:text-cafe-400 hover:text-cafe-900 dark:hover:text-cafe-100'"
                        >
                            <CalendarMonthRegular class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            <span>{{ t('attendance.daily_rollcall') }}</span>
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'matrix'"
                            class="px-4 py-2 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-1.5"
                            :class="activeTab === 'matrix'
                                ? 'bg-white dark:bg-surface-dark-1 text-cafe-900 dark:text-cafe-50 shadow-xs'
                                : 'text-cafe-600 dark:text-cafe-400 hover:text-cafe-900 dark:hover:text-cafe-100'"
                        >
                            <GroupRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                            <span>{{ t('attendance.matrix_view') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- TAB 1: PASE DE LISTA DIARIO (ROLL CALL)                     -->
            <!-- ═══════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'rollcall'" class="space-y-5">
                <!-- Date Bar & Navigation Controls -->
                <div class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Quick Day Selector -->
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="stepDate(-1)"
                            class="p-2 rounded-xl border border-cafe-200 dark:border-surface-dark-3 text-cafe-700 dark:text-cafe-300 hover:bg-cafe-100 dark:hover:bg-surface-dark-2 transition-colors"
                            :title="t('attendance.yesterday')"
                        >
                            <LeftSmallRegular class="w-4 h-4" />
                        </button>

                        <button
                            type="button"
                            @click="setToday"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border transition-colors"
                            :class="selectedDate === new Date().toISOString().split('T')[0]
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                                : 'bg-white dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300 border-cafe-200 dark:border-surface-dark-3 hover:bg-cafe-100'"
                        >
                            {{ t('attendance.today') }}
                        </button>

                        <button
                            type="button"
                            @click="stepDate(1)"
                            class="p-2 rounded-xl border border-cafe-200 dark:border-surface-dark-3 text-cafe-700 dark:text-cafe-300 hover:bg-cafe-100 dark:hover:bg-surface-dark-2 transition-colors"
                            :title="t('attendance.tomorrow')"
                        >
                            <RightSmallRegular class="w-4 h-4" />
                        </button>

                        <!-- Native Date Input -->
                        <input
                            type="date"
                            v-model="selectedDate"
                            @change="changeDate(selectedDate)"
                            class="cp-input text-xs font-medium py-1.5 px-3 rounded-xl border border-cafe-200 dark:border-surface-dark-3"
                        />
                    </div>

                    <!-- Date Label & Unsaved Status -->
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-serif font-bold text-cafe-800 dark:text-cafe-200 capitalize">
                            {{ formatDisplayDate(selectedDate) }}
                        </span>

                        <span
                            v-if="isDirty"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200/60 animate-pulse"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500" />
                            {{ t('attendance.unsaved_changes') }}
                        </span>
                    </div>
                </div>

                <!-- Live KPI Counter Ribbon -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div class="rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-white/80 dark:bg-surface-dark-1 p-3 text-center shadow-2xs">
                        <span class="block text-[11px] text-cafe-500 dark:text-cafe-400">{{ t('attendance.total_students') }}</span>
                        <span class="block text-xl font-bold text-cafe-900 dark:text-cafe-100 mt-0.5">{{ liveCounts.total }}</span>
                    </div>

                    <div class="rounded-xl border border-emerald-200/60 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/20 p-3 text-center shadow-2xs">
                        <span class="block text-[11px] text-emerald-700 dark:text-emerald-400 font-medium">{{ t('attendance.present_count') }}</span>
                        <span class="block text-xl font-bold text-emerald-800 dark:text-emerald-300 mt-0.5">
                            {{ liveCounts.present }} <span class="text-xs font-normal text-emerald-600">({{ liveCounts.rate }}%)</span>
                        </span>
                    </div>

                    <div class="rounded-xl border border-red-200/60 dark:border-red-900/50 bg-red-50/50 dark:bg-red-950/20 p-3 text-center shadow-2xs">
                        <span class="block text-[11px] text-red-700 dark:text-red-400 font-medium">{{ t('attendance.absent_count') }}</span>
                        <span class="block text-xl font-bold text-red-800 dark:text-red-300 mt-0.5">{{ liveCounts.absent }}</span>
                    </div>

                    <div class="rounded-xl border border-amber-200/60 dark:border-amber-900/50 bg-amber-50/50 dark:bg-amber-950/20 p-3 text-center shadow-2xs">
                        <span class="block text-[11px] text-amber-700 dark:text-amber-400 font-medium">{{ t('attendance.justified_count') }}</span>
                        <span class="block text-xl font-bold text-amber-800 dark:text-amber-300 mt-0.5">{{ liveCounts.justified }}</span>
                    </div>

                    <div class="rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-white/80 dark:bg-surface-dark-1 p-3 text-center shadow-2xs col-span-2 sm:col-span-1">
                        <span class="block text-[11px] text-cafe-500 dark:text-cafe-400">{{ t('attendance.pending_count') }}</span>
                        <span class="block text-xl font-bold text-cafe-700 dark:text-cafe-300 mt-0.5">{{ liveCounts.pending }}</span>
                    </div>
                </div>

                <!-- Quick Batch Toolbar & Student Filter -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            type="button"
                            @click="setAll('present')"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-200 border border-emerald-200/60 transition-colors"
                        >
                            {{ t('attendance.all_present') }}
                        </button>
                        <button
                            type="button"
                            @click="setAll('absent')"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-red-100 dark:bg-red-950/60 text-red-800 dark:text-red-300 hover:bg-red-200 border border-red-200/60 transition-colors"
                        >
                            {{ t('attendance.all_absent') }}
                        </button>
                        <button
                            type="button"
                            @click="resetAll"
                            class="px-3 py-1.5 rounded-xl text-xs font-medium text-cafe-600 dark:text-cafe-400 hover:bg-cafe-100 dark:hover:bg-surface-dark-2 border border-cafe-200 dark:border-surface-dark-3 transition-colors"
                        >
                            {{ t('attendance.reset_marks') }}
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <Search2Regular class="w-4 h-4 text-cafe-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            :placeholder="t('attendance.search_student')"
                            class="cp-input text-xs w-full pl-9 py-1.5 rounded-xl"
                        />
                    </div>
                </div>

                <!-- Students Roll Call Cards / Rows -->
                <div v-if="filteredStudents.length === 0" class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-8 text-center text-cafe-500">
                    {{ t('groups.no_students') }}
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="(student, index) in filteredStudents"
                        :key="student.id"
                        class="rounded-xl border p-3.5 bg-white/95 dark:bg-surface-dark-1 flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 transition-all duration-150"
                        :class="[
                            localStatus[student.id] === 'present' ? 'border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/10' :
                            localStatus[student.id] === 'absent' ? 'border-red-200 dark:border-red-900/60 bg-red-50/10' :
                            localStatus[student.id] === 'justified' ? 'border-amber-200 dark:border-amber-900/60 bg-amber-50/10' :
                            'border-cafe-200/80 dark:border-surface-dark-3'
                        ]"
                    >
                        <!-- Student Profile & Badges -->
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-mono text-cafe-400 w-5 text-right">{{ index + 1 }}</span>
                            <div class="w-9 h-9 rounded-full bg-accent-100 dark:bg-accent-950 text-accent-700 dark:text-accent-300 font-serif font-bold text-xs flex items-center justify-center shrink-0 border border-accent-200/60">
                                {{ student.initials }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="route('students.show', student.slug)"
                                        class="font-semibold text-sm text-cafe-900 dark:text-cafe-50 hover:text-accent-600 dark:hover:text-accent-400 transition-colors"
                                    >
                                        {{ student.full_name }}
                                    </Link>
                                    <!-- Absence Alert Flag -->
                                    <span
                                        v-if="student.summary.has_absence_alert"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 border border-red-200"
                                        :title="t('attendance.absence_alert')"
                                    >
                                        <AlertRegular class="w-3 h-3" />
                                        <span>{{ t('attendance.consecutive_absences', { count: student.summary.absence_streak }) }}</span>
                                    </span>
                                </div>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                    <span>{{ t('attendance.overall_rate') }}: </span>
                                    <span class="font-medium text-cafe-700 dark:text-cafe-300">
                                        {{ student.summary.rate !== null ? `${student.summary.rate}%` : '—' }}
                                    </span>
                                    <span class="mx-1.5 text-cafe-300">·</span>
                                    <span>{{ t('attendance.absences', { count: student.summary.absent }) }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Ergonomic 3-Button Status Switcher -->
                        <div class="flex items-center gap-1.5 self-end sm:self-center">
                            <!-- Present Button -->
                            <button
                                type="button"
                                @click="setStudentStatus(student.id, 'present')"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all duration-150"
                                :class="localStatus[student.id] === 'present'
                                    ? 'bg-emerald-600 text-white shadow-xs font-bold'
                                    : 'bg-cafe-100/70 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300 hover:bg-emerald-100 dark:hover:bg-emerald-950/40'"
                            >
                                <CheckRegular class="w-3.5 h-3.5" />
                                <span>{{ t('attendance.present') }}</span>
                            </button>

                            <!-- Absent Button -->
                            <button
                                type="button"
                                @click="setStudentStatus(student.id, 'absent')"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all duration-150"
                                :class="localStatus[student.id] === 'absent'
                                    ? 'bg-red-600 text-white shadow-xs font-bold'
                                    : 'bg-cafe-100/70 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300 hover:bg-red-100 dark:hover:bg-red-950/40'"
                            >
                                <span>{{ t('attendance.absent') }}</span>
                            </button>

                            <!-- Justified Button -->
                            <button
                                type="button"
                                @click="setStudentStatus(student.id, 'justified')"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all duration-150"
                                :class="localStatus[student.id] === 'justified'
                                    ? 'bg-amber-600 text-white shadow-xs font-bold'
                                    : 'bg-cafe-100/70 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300 hover:bg-amber-100 dark:hover:bg-amber-950/40'"
                            >
                                <span>{{ t('attendance.justified') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sticky Save Bar -->
                <div class="sticky bottom-4 z-20 rounded-2xl border border-cafe-300/80 dark:border-surface-dark-3 bg-white/95 dark:bg-surface-dark-1/95 backdrop-blur-md p-4 shadow-lg flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-xs text-cafe-600 dark:text-cafe-400">
                        <span v-if="isDirty" class="w-2 h-2 rounded-full bg-amber-500 animate-ping" />
                        <span>
                            {{ isDirty ? t('attendance.unsaved_changes') : t('attendance.up_to_date') }}
                        </span>
                        <span class="hidden sm:inline text-cafe-400">
                            · {{ saveShortcutParts[0] }}<kbd class="px-1.5 py-0.5 rounded bg-cafe-100 dark:bg-surface-dark-2 font-mono text-[10px] text-cafe-700 dark:text-cafe-300 border border-cafe-200 dark:border-cafe-700">{{ saveShortcutLabel }}</kbd>{{ saveShortcutParts[1] || '' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <CpButton
                            type="button"
                            variant="primary"
                            :disabled="isSaving"
                            @click="saveAttendance"
                        >
                            {{ isSaving ? t('attendance.saving') : t('attendance.save') }}
                        </CpButton>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- TAB 2: MATRIZ E HISTORIAL DEL GRUPO                         -->
            <!-- ═══════════════════════════════════════════════════════════ -->
            <div v-else-if="activeTab === 'matrix'" class="space-y-5">
                <div class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-cafe-100 dark:border-surface-dark-3">
                        <div>
                            <h2 class="text-base font-bold text-cafe-900 dark:text-cafe-100">
                                {{ t('attendance.matrix_view') }}
                            </h2>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                {{ t('attendance.matrix_subtitle', { count: sessionDates.length }) }}
                            </p>
                        </div>

                        <!-- Legend -->
                        <div class="flex items-center gap-3 text-xs font-medium">
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block" />
                                <span>P ({{ t('attendance.present') }})</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-red-500 inline-block" />
                                <span>A ({{ t('attendance.absent') }})</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-amber-500 inline-block" />
                                <span>J ({{ t('attendance.justified') }})</span>
                            </span>
                        </div>
                    </div>

                    <!-- Matrix Table -->
                    <div v-if="sessionDates.length === 0" class="py-12 text-center text-cafe-500">
                        {{ t('attendance.no_records_yet') }}
                    </div>

                    <div v-else class="overflow-x-auto mt-4 rounded-xl border border-cafe-200/80 dark:border-surface-dark-3">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="bg-cafe-100/70 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 border-b border-cafe-200 dark:border-surface-dark-3">
                                    <th class="p-3 sticky left-0 bg-cafe-100 dark:bg-surface-dark-2 font-bold min-w-[180px] z-10 shadow-r">
                                        {{ t('students.name') }}
                                    </th>
                                    <th
                                        v-for="sDate in sessionDates"
                                        :key="sDate"
                                        class="p-2 text-center font-mono font-medium whitespace-nowrap min-w-[48px]"
                                        :class="{ 'bg-accent-100/50 dark:bg-accent-950/40 text-accent-800 dark:text-accent-300 font-bold': sDate === selectedDate }"
                                    >
                                        {{ formatShortDate(sDate) }}
                                    </th>
                                    <th class="p-3 text-center font-bold min-w-[70px]">{{ t('attendance.rate') }}</th>
                                    <th class="p-3 text-center font-bold min-w-[60px]">{{ t('attendance.absent_count') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="student in students"
                                    :key="student.id"
                                    class="border-b border-cafe-100 dark:border-surface-dark-3 hover:bg-cafe-50 dark:hover:bg-surface-dark-2/40 transition-colors"
                                >
                                    <!-- Sticky Student Name -->
                                    <td class="p-3 sticky left-0 bg-white dark:bg-surface-dark-1 font-medium text-cafe-900 dark:text-cafe-50 z-10 border-r border-cafe-100 dark:border-surface-dark-3">
                                        <Link
                                            :href="route('students.show', student.slug)"
                                            class="hover:text-accent-600 transition-colors"
                                        >
                                            {{ student.full_name }}
                                        </Link>
                                    </td>

                                    <!-- Session Status Cells -->
                                    <td
                                        v-for="sDate in sessionDates"
                                        :key="sDate"
                                        class="p-2 text-center"
                                        :class="{ 'bg-accent-50/30 dark:bg-accent-950/20': sDate === selectedDate }"
                                    >
                                        <span
                                            v-if="student.matrix[sDate] === 'present'"
                                            class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 font-bold text-[10px]"
                                            :title="t('attendance.present')"
                                        >
                                            P
                                        </span>
                                        <span
                                            v-else-if="student.matrix[sDate] === 'absent'"
                                            class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-red-100 dark:bg-red-950/70 text-red-800 dark:text-red-300 font-bold text-[10px]"
                                            :title="t('attendance.absent')"
                                        >
                                            A
                                        </span>
                                        <span
                                            v-else-if="student.matrix[sDate] === 'justified'"
                                            class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 font-bold text-[10px]"
                                            :title="t('attendance.justified')"
                                        >
                                            J
                                        </span>
                                        <span
                                            v-else
                                            class="inline-block text-cafe-300 dark:text-cafe-600"
                                        >
                                            ·
                                        </span>
                                    </td>

                                    <!-- Accumulated Rate -->
                                    <td class="p-3 text-center font-bold font-mono text-cafe-800 dark:text-cafe-200">
                                        {{ student.summary.rate !== null ? `${student.summary.rate}%` : '—' }}
                                    </td>

                                    <!-- Absence Count -->
                                    <td class="p-3 text-center font-semibold" :class="student.summary.absent > 3 ? 'text-red-600 font-bold' : 'text-cafe-600 dark:text-cafe-400'">
                                        {{ student.summary.absent }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
