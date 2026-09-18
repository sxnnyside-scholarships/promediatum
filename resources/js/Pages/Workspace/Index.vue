<script setup lang="ts">
/**
 * Workspace Index — /workspace
 * High-utility educator cockpit following the Café Pedagógico Design System:
 * - Personalized teacher greeting & pedagogical focus of the day
 * - 1-Click quick actions: Attendance roll call, grading, student creation, observation note
 * - Academic term progress bar & live KPI metrics
 * - Interactive group cards with direct 1-click links to Roll Call & Gradebook
 * - Pending observations triage with 1-click resolution and StudentAvatar
 * - Formative early-warning insights & automated pedagogical actions
 * - Group picker modals for rapid attendance & grading
 */

import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AddCircleRegular,
    AlertRegular,
    ArrowRightRegular,
    AwardRegular,
    Book2Regular,
    BulbRegular,
    CalendarRegular,
    CheckCircleRegular,
    CloseCircleRegular,
    DashboardRegular,
    Edit3Regular,
    EyeRegular,
    GroupRegular,
    NotebookRegular,
    RightSmallRegular,
    SparklesRegular,
    TimeRegular,
    UserAddRegular,
} from '@mingcute/vue/core-regular';
import { computed, ref } from 'vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import { useToast } from '@/composables/useToast';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const { t } = useTranslations();
const toast = useToast();
const page = usePage();

interface GroupItem {
    id: number;
    name: string;
    slug: string;
    subject?: string | null;
    grade_level?: string | null;
    students_count: number;
}

interface StudentAvatarData {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    initials: string;
    slug?: string;
}

interface PendingObs {
    id: number;
    type: string;
    content: string;
    student?: StudentAvatarData | null;
    student_name?: string | null;
    student_slug?: string | null;
    group_name?: string | null;
    group_slug?: string | null;
    created_at?: string | null;
}

interface RecentAct {
    id: number;
    type: string;
    status: string;
    content: string;
    student?: StudentAvatarData | null;
    student_name?: string | null;
    group_name?: string | null;
    created_at?: string | null;
}

interface InsightItem {
    severity: 'critical' | 'high' | 'medium' | 'low';
    message: string;
    suggested_action?: string;
    route?: string;
}

interface SuggestedActionItem {
    severity: 'critical' | 'high' | 'medium' | 'low';
    title: string;
    description: string;
    route?: string;
    icon?: string;
}

interface ActivePeriodData {
    id: number;
    name: string;
    slug: string;
    start_date: string | null;
    end_date: string | null;
    days_remaining: number;
    progress_percent?: number;
}

interface WorkspaceStats {
    total_students: number;
    total_groups: number;
    pending_observations: number;
}

const props = defineProps<{
    activePeriod?: ActivePeriodData | null;
    groups?: GroupItem[];
    pendingObservations?: PendingObs[];
    recentActivity?: RecentAct[];
    insights?: InsightItem[];
    suggestedActions?: SuggestedActionItem[];
    stats?: WorkspaceStats;
}>();

const authUser = computed(() => page.props.auth?.user as Record<string, any> | null);

const teacherGreeting = computed(() => {
    if (authUser.value?.localized_greeting) {
        return authUser.value.localized_greeting;
    }
    if (authUser.value?.greeting) {
        return authUser.value.greeting;
    }
    const hour = new Date().getHours();
    let prefix = t('workspace.welcome_morning');
    if (hour >= 12 && hour < 19) {
        prefix = t('workspace.welcome_afternoon');
    } else if (hour >= 19 || hour < 5) {
        prefix = t('workspace.welcome_evening');
    }
    const name = authUser.value?.first_name || authUser.value?.full_name || '';
    return name ? `${prefix}, ${name}` : prefix;
});

// ── Modals for rapid group selection ──
const showAttendanceModal = ref(false);
const showGradingModal = ref(false);

function handleQuickAttendance() {
    if (!props.groups || props.groups.length === 0) {
        router.visit(route('groups.create'));
        return;
    }
    if (props.groups.length === 1) {
        router.visit(route('attendance.index', props.groups[0].id));
        return;
    }
    showAttendanceModal.value = true;
}

function handleQuickGrading() {
    if (!props.groups || props.groups.length === 0) {
        router.visit(route('groups.create'));
        return;
    }
    if (props.groups.length === 1) {
        router.visit(route('groups.show', props.groups[0].slug));
        return;
    }
    showGradingModal.value = true;
}

// ── Toggle observation resolution ──
const togglingId = ref<number | null>(null);

function toggleObservation(id: number) {
    togglingId.value = id;
    router.post(
        route('observations.toggle-resolved', id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(t('workspace.resolve_now'));
            },
            onFinish: () => {
                togglingId.value = null;
            },
        },
    );
}

// ── Severity styles for Insights ──
const insightSeverityClasses: Record<
    string,
    { border: string; bg: string; text: string; badge: string }
> = {
    critical: {
        border: 'border-l-rose-500 dark:border-l-rose-400',
        bg: 'bg-rose-50/70 dark:bg-rose-950/20',
        text: 'text-rose-800 dark:text-rose-200',
        badge: 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200',
    },
    high: {
        border: 'border-l-amber-500 dark:border-l-amber-400',
        bg: 'bg-amber-50/70 dark:bg-amber-950/20',
        text: 'text-amber-800 dark:text-amber-200',
        badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
    },
    medium: {
        border: 'border-l-accent-500 dark:border-l-accent-400',
        bg: 'bg-accent-50/60 dark:bg-accent-950/20',
        text: 'text-accent-800 dark:text-accent-200',
        badge: 'bg-accent-100 text-accent-800 dark:bg-accent-900/50 dark:text-accent-200',
    },
    low: {
        border: 'border-l-sky-500 dark:border-l-sky-400',
        bg: 'bg-sky-50/60 dark:bg-sky-950/20',
        text: 'text-sky-800 dark:text-sky-200',
        badge: 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200',
    },
};

const observationTypeTheme: Record<string, { bg: string; text: string; border: string }> = {
    performance: {
        bg: 'bg-blue-50 dark:bg-blue-950/30',
        text: 'text-blue-700 dark:text-blue-300',
        border: 'border-blue-200/60 dark:border-blue-900/40',
    },
    behavior: {
        bg: 'bg-amber-50 dark:bg-amber-950/30',
        text: 'text-amber-700 dark:text-amber-300',
        border: 'border-amber-200/60 dark:border-amber-900/40',
    },
    achievement: {
        bg: 'bg-emerald-50 dark:bg-emerald-950/30',
        text: 'text-emerald-700 dark:text-emerald-300',
        border: 'border-emerald-200/60 dark:border-emerald-900/40',
    },
    followup: {
        bg: 'bg-indigo-50 dark:bg-indigo-950/30',
        text: 'text-indigo-700 dark:text-indigo-300',
        border: 'border-indigo-200/60 dark:border-indigo-900/40',
    },
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('workspace.title')" />

        <div class="space-y-6">
            <!-- ═══ 1. TEACHER HERO & QUICK ACTIONS ═══ -->
            <section
                class="relative overflow-hidden rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-6 lg:p-8 shadow-sm"
            >
                <div class="relative z-10 space-y-6">
                    <!-- Top greeting header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cafe-100/90 dark:bg-surface-dark-2 border border-cafe-200/60 dark:border-surface-dark-3 text-xs font-semibold text-cafe-800 dark:text-cafe-200 mb-2">
                                <DashboardRegular class="w-3.5 h-3.5 text-accent-600 dark:text-accent-400" />
                                <span>{{ t('workspace.title') }}</span>
                                <span v-if="authUser?.institution" class="text-cafe-400 dark:text-cafe-500">·</span>
                                <span v-if="authUser?.institution" class="font-normal">{{ authUser.institution }}</span>
                            </div>

                            <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-cafe-900 dark:text-cafe-50 font-serif">
                                {{ teacherGreeting }}
                            </h1>
                            <p class="text-sm text-cafe-600 dark:text-cafe-300 mt-1 max-w-2xl">
                                {{ t('workspace.subtitle') }}
                            </p>
                        </div>

                        <!-- Micro date badge -->
                        <div class="shrink-0 flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/80 dark:bg-surface-dark-2/80 border border-cafe-200/60 dark:border-surface-dark-3 text-xs font-medium text-cafe-700 dark:text-cafe-300">
                            <CalendarRegular class="w-4 h-4 text-accent-500" />
                            <span>{{ new Date().toLocaleDateString(page.props.locale === 'es' ? 'es-MX' : 'en-US', { weekday: 'long', day: 'numeric', month: 'long' }) }}</span>
                        </div>
                    </div>

                    <!-- Ergonomic Quick Actions Toolbar -->
                    <div class="pt-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-cafe-500 dark:text-cafe-400 mb-3 flex items-center gap-1.5">
                            <SparklesRegular class="w-3.5 h-3.5 text-amber-500" />
                            <span>{{ t('workspace.quick_actions') }}</span>
                        </p>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <!-- Quick Action: Take Attendance -->
                            <button
                                type="button"
                                @click="handleQuickAttendance"
                                class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-surface-dark-2/90 border border-cafe-200/80 dark:border-surface-dark-3 hover:border-emerald-300 dark:hover:border-emerald-700 hover:shadow-xs transition-all text-left group"
                            >
                                <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <CheckCircleRegular class="w-5 h-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-cafe-900 dark:text-cafe-100 truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                        {{ t('workspace.take_attendance') }}
                                    </p>
                                    <p class="text-[11px] text-cafe-500 dark:text-cafe-400 truncate">
                                        {{ t('attendance.title') }}
                                    </p>
                                </div>
                            </button>

                            <!-- Quick Action: Grade Group -->
                            <button
                                type="button"
                                @click="handleQuickGrading"
                                class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-surface-dark-2/90 border border-cafe-200/80 dark:border-surface-dark-3 hover:border-amber-300 dark:hover:border-amber-700 hover:shadow-xs transition-all text-left group"
                            >
                                <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <AwardRegular class="w-5 h-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-cafe-900 dark:text-cafe-100 truncate group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                        {{ t('workspace.record_grade') }}
                                    </p>
                                    <p class="text-[11px] text-cafe-500 dark:text-cafe-400 truncate">
                                        {{ t('grades.title') }}
                                    </p>
                                </div>
                            </button>

                            <!-- Quick Action: New Observation -->
                            <Link
                                :href="route('observations.index', { new: 1 })"
                                class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-surface-dark-2/90 border border-cafe-200/80 dark:border-surface-dark-3 hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-xs transition-all text-left group"
                            >
                                <div class="w-9 h-9 rounded-lg bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <NotebookRegular class="w-5 h-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-cafe-900 dark:text-cafe-100 truncate group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                                        {{ t('workspace.new_observation') }}
                                    </p>
                                    <p class="text-[11px] text-cafe-500 dark:text-cafe-400 truncate">
                                        {{ t('nav.observations') }}
                                    </p>
                                </div>
                            </Link>

                            <!-- Quick Action: New Student -->
                            <Link
                                :href="route('students.create')"
                                class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-surface-dark-2/90 border border-cafe-200/80 dark:border-surface-dark-3 hover:border-accent-300 dark:hover:border-accent-700 hover:shadow-xs transition-all text-left group"
                            >
                                <div class="w-9 h-9 rounded-lg bg-cafe-100 dark:bg-surface-dark-3 text-cafe-800 dark:text-cafe-200 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <UserAddRegular class="w-5 h-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-cafe-900 dark:text-cafe-100 truncate group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors">
                                        {{ t('workspace.new_student') }}
                                    </p>
                                    <p class="text-[11px] text-cafe-500 dark:text-cafe-400 truncate">
                                        {{ t('nav.students') }}
                                    </p>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Pedagogical Focus Tip Banner -->
                    <div class="flex items-start gap-3 p-3.5 rounded-xl bg-cafe-50/90 dark:bg-surface-dark-2/70 border border-cafe-200/60 dark:border-surface-dark-3">
                        <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                            <BulbRegular class="w-4 h-4" />
                        </div>
                        <div class="text-xs">
                            <span class="font-bold text-cafe-800 dark:text-cafe-200">{{ t('workspace.pedagogical_focus') }}: </span>
                            <span class="text-cafe-600 dark:text-cafe-300 leading-relaxed">{{ t('workspace.pedagogical_focus_text') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Decorative ambient glow -->
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-amber-200/20 dark:bg-accent-500/10 rounded-full blur-3xl pointer-events-none" />
            </section>

            <!-- ═══ 2. KEY METRICS & ACADEMIC PROGRESS ═══ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Active Period & Progress -->
                <div class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wide flex items-center gap-1.5">
                                <CalendarRegular class="w-4 h-4 text-accent-500" />
                                {{ t('workspace.active_period') }}
                            </span>
                            <span v-if="activePeriod" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40">
                                {{ activePeriod.days_remaining }} {{ t('periods.days_remaining').toLowerCase() }}
                            </span>
                        </div>

                        <template v-if="activePeriod">
                            <Link :href="route('periods.show', activePeriod.slug)" class="group block">
                                <p class="text-lg font-bold text-cafe-900 dark:text-cafe-100 group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors truncate">
                                    {{ activePeriod.name }}
                                </p>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5 truncate">
                                    {{ activePeriod.start_date }} → {{ activePeriod.end_date }}
                                </p>
                            </Link>
                        </template>
                        <template v-else>
                            <p class="text-sm font-medium text-cafe-500 dark:text-cafe-400">
                                {{ t('workspace.no_active_period') }}
                            </p>
                            <Link :href="route('periods.create')" class="inline-flex items-center gap-1 text-xs text-accent-600 font-semibold mt-2 hover:underline">
                                <AddCircleRegular class="w-3.5 h-3.5" />
                                {{ t('periods.create') }}
                            </Link>
                        </template>
                    </div>

                    <!-- Period Progress Bar -->
                    <div v-if="activePeriod" class="mt-4 pt-3 border-t border-cafe-100 dark:border-surface-dark-3">
                        <div class="flex items-center justify-between text-[11px] text-cafe-500 dark:text-cafe-400 mb-1.5">
                            <span>{{ t('workspace.period_progress') }}</span>
                            <span class="font-bold text-cafe-800 dark:text-cafe-200">{{ activePeriod.progress_percent ?? 0 }}%</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-cafe-100 dark:bg-surface-dark-3 overflow-hidden">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-amber-500 to-accent-600 transition-all duration-500"
                                :style="{ width: `${activePeriod.progress_percent ?? 0}%` }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Card 2: Active Groups -->
                <Link
                    :href="route('groups.index')"
                    class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-5 shadow-xs hover:border-cafe-300 dark:hover:border-surface-dark-4 transition-all group flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wide flex items-center gap-1.5">
                            <GroupRegular class="w-4 h-4 text-blue-500" />
                            {{ t('nav.groups') }}
                        </span>
                        <RightSmallRegular class="w-4 h-4 text-cafe-400 group-hover:translate-x-0.5 transition-transform" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-cafe-900 dark:text-cafe-100">
                            {{ stats?.total_groups ?? 0 }}
                        </p>
                        <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-1">
                            {{ t('workspace.my_groups_subtitle') }}
                        </p>
                    </div>
                </Link>

                <!-- Card 3: Total Students -->
                <Link
                    :href="route('students.index')"
                    class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-5 shadow-xs hover:border-cafe-300 dark:hover:border-surface-dark-4 transition-all group flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wide flex items-center gap-1.5">
                            <UserAddRegular class="w-4 h-4 text-purple-500" />
                            {{ t('nav.students') }}
                        </span>
                        <RightSmallRegular class="w-4 h-4 text-cafe-400 group-hover:translate-x-0.5 transition-transform" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-cafe-900 dark:text-cafe-100">
                            {{ stats?.total_students ?? 0 }}
                        </p>
                        <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-1">
                            {{ t('students.subtitle') }}
                        </p>
                    </div>
                </Link>

                <!-- Card 4: Pending Observations -->
                <Link
                    :href="route('observations.index')"
                    class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-5 shadow-xs hover:border-cafe-300 dark:hover:border-surface-dark-4 transition-all group flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wide flex items-center gap-1.5">
                            <NotebookRegular class="w-4 h-4 text-amber-500" />
                            {{ t('workspace.pending') }}
                        </span>
                        <span
                            v-if="(stats?.pending_observations ?? 0) > 0"
                            class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300"
                        >
                            {{ stats?.pending_observations }}
                        </span>
                    </div>
                    <div>
                        <p class="text-3xl font-bold" :class="(stats?.pending_observations ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-cafe-900 dark:text-cafe-100'">
                            {{ stats?.pending_observations ?? 0 }}
                        </p>
                        <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-1">
                            {{ t('workspace.pending_observations') }}
                        </p>
                    </div>
                </Link>
            </div>

            <!-- ═══ 3. MAIN BENTO GRID (2 COLUMNS) ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <!-- ── Left Column (2 cols): My Groups & Pending Observations ── -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- SECTION: My Academic Groups -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs">
                        <div class="flex items-center justify-between gap-4 mb-5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-cafe-100 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 flex items-center justify-center shrink-0">
                                    <GroupRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ t('workspace.my_groups') }}
                                    </h2>
                                    <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                        {{ t('workspace.my_groups_subtitle') }}
                                    </p>
                                </div>
                            </div>

                            <Link
                                :href="route('groups.index')"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-accent-600 dark:text-accent-400 hover:text-accent-700 transition-colors"
                            >
                                <span>{{ t('workspace.view_all') }}</span>
                                <RightSmallRegular class="w-4 h-4" />
                            </Link>
                        </div>

                        <!-- Groups Grid with 1-Click Action Buttons -->
                        <div v-if="groups && groups.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div
                                v-for="group in groups"
                                :key="group.id"
                                class="rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/50 p-4 hover:border-cafe-300 dark:hover:border-surface-dark-4 hover:shadow-xs transition-all flex flex-col justify-between gap-3"
                            >
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <Link :href="route('groups.show', group.slug)" class="group min-w-0">
                                            <h3 class="text-sm font-bold text-cafe-900 dark:text-cafe-100 group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors truncate">
                                                {{ group.name }}
                                            </h3>
                                            <p v-if="group.subject" class="text-xs text-cafe-600 dark:text-cafe-300 truncate mt-0.5">
                                                {{ group.subject }}
                                            </p>
                                        </Link>

                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-white dark:bg-surface-dark-3 text-cafe-700 dark:text-cafe-300 border border-cafe-200/60 dark:border-surface-dark-4 shrink-0">
                                            <GroupRegular class="w-3 h-3 text-cafe-400" />
                                            <span>{{ group.students_count }}</span>
                                        </span>
                                    </div>

                                    <div v-if="group.grade_level" class="mt-2">
                                        <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-medium bg-cafe-100 dark:bg-surface-dark-3 text-cafe-700 dark:text-cafe-300">
                                            {{ group.grade_level }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Action bar for group -->
                                <div class="pt-2 border-t border-cafe-200/60 dark:border-surface-dark-3 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <Link
                                            :href="route('attendance.index', group.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors"
                                            :title="t('workspace.take_attendance')"
                                        >
                                            <CheckCircleRegular class="w-3.5 h-3.5" />
                                            <span>{{ t('attendance.title') }}</span>
                                        </Link>

                                        <Link
                                            :href="route('groups.show', group.slug)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors"
                                            :title="t('workspace.record_grade')"
                                        >
                                            <AwardRegular class="w-3.5 h-3.5" />
                                            <span>{{ t('grades.title') }}</span>
                                        </Link>
                                    </div>

                                    <Link
                                        :href="route('groups.show', group.slug)"
                                        class="p-1.5 rounded-lg text-cafe-400 hover:text-cafe-700 dark:hover:text-cafe-200 hover:bg-cafe-100 dark:hover:bg-surface-dark-3 transition-colors"
                                        :title="t('workspace.view_group')"
                                    >
                                        <ArrowRightRegular class="w-4 h-4" />
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-10 px-4 rounded-xl border border-dashed border-cafe-200 dark:border-surface-dark-3">
                            <GroupRegular class="w-10 h-10 text-cafe-300 dark:text-cafe-600 mx-auto mb-2" />
                            <p class="text-sm font-medium text-cafe-600 dark:text-cafe-300">
                                {{ t('workspace.no_groups') }}
                            </p>
                            <Link
                                :href="route('groups.create')"
                                class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 rounded-lg bg-accent-600 text-white text-xs font-semibold hover:bg-accent-700 transition-colors shadow-xs"
                            >
                                <AddCircleRegular class="w-4 h-4" />
                                <span>{{ t('groups.create') }}</span>
                            </Link>
                        </div>
                    </section>

                    <!-- SECTION: Pending Observations (Formative Triage) -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs">
                        <div class="flex items-center justify-between gap-4 mb-5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0">
                                    <NotebookRegular class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ t('workspace.pending_observations') }}
                                    </h2>
                                    <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                        {{ t('observations.subtitle') }}
                                    </p>
                                </div>
                            </div>

                            <Link
                                :href="route('observations.index')"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-accent-600 dark:text-accent-400 hover:text-accent-700 transition-colors"
                            >
                                <span>{{ t('workspace.view_all') }}</span>
                                <RightSmallRegular class="w-4 h-4" />
                            </Link>
                        </div>

                        <!-- Pending Observations List -->
                        <div v-if="pendingObservations && pendingObservations.length > 0" class="space-y-3">
                            <div
                                v-for="obs in pendingObservations"
                                :key="obs.id"
                                class="rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/50 p-3.5 hover:border-cafe-300 dark:hover:border-surface-dark-4 transition-all flex items-start justify-between gap-3"
                            >
                                <div class="flex items-start gap-3 min-w-0">
                                    <StudentAvatar :student="obs.student" size="sm" class="mt-0.5 shrink-0" />
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <span class="text-xs font-bold text-cafe-900 dark:text-cafe-100 truncate">
                                                {{ obs.student_name }}
                                            </span>
                                            <span v-if="obs.group_name" class="text-xs text-cafe-400 dark:text-cafe-500">·</span>
                                            <span v-if="obs.group_name" class="text-xs text-cafe-600 dark:text-cafe-300 truncate font-medium">
                                                {{ obs.group_name }}
                                            </span>
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                                :class="[
                                                    observationTypeTheme[obs.type]?.bg,
                                                    observationTypeTheme[obs.type]?.text,
                                                    observationTypeTheme[obs.type]?.border,
                                                ]"
                                            >
                                                {{ t('observations.type_' + obs.type) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-cafe-700 dark:text-cafe-200 leading-relaxed line-clamp-2">
                                            {{ obs.content }}
                                        </p>
                                        <p class="text-[11px] text-cafe-400 dark:text-cafe-500 mt-1 flex items-center gap-1">
                                            <TimeRegular class="w-3 h-3" />
                                            <span>{{ obs.created_at }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- 1-Click Quick Resolve Button -->
                                <button
                                    type="button"
                                    @click="toggleObservation(obs.id)"
                                    :disabled="togglingId === obs.id"
                                    class="shrink-0 p-1.5 rounded-lg text-cafe-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition-colors"
                                    :title="t('workspace.resolve_now')"
                                >
                                    <CheckCircleRegular class="w-5 h-5" :class="{ 'animate-spin': togglingId === obs.id }" />
                                </button>
                            </div>
                        </div>

                        <!-- Positive Empty State -->
                        <div v-else class="text-center py-8 px-4 rounded-xl bg-emerald-50/40 dark:bg-emerald-950/10 border border-emerald-200/50 dark:border-emerald-900/30">
                            <CheckCircleRegular class="w-8 h-8 text-emerald-500 dark:text-emerald-400 mx-auto mb-2" />
                            <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-200">
                                {{ t('workspace.no_pending') }}
                            </p>
                        </div>
                    </section>

                    <!-- SECTION: Recent Activity Timeline -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs">
                        <div class="flex items-center gap-2.5 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-cafe-100 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 flex items-center justify-center shrink-0">
                                <TimeRegular class="w-4 h-4 text-cafe-600 dark:text-cafe-400" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-cafe-900 dark:text-cafe-100">
                                    {{ t('workspace.recent_activity') }}
                                </h2>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                    {{ t('workspace.activity_subtitle') }}
                                </p>
                            </div>
                        </div>

                        <div v-if="recentActivity && recentActivity.length > 0" class="divide-y divide-cafe-100 dark:divide-surface-dark-3">
                            <div
                                v-for="item in recentActivity"
                                :key="item.id"
                                class="py-3 first:pt-0 last:pb-0 flex items-start gap-3"
                            >
                                <StudentAvatar :student="item.student" size="sm" class="mt-0.5 shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap text-xs">
                                        <span class="font-semibold text-cafe-900 dark:text-cafe-100">
                                            {{ item.student_name }}
                                        </span>
                                        <span v-if="item.group_name" class="text-cafe-400">·</span>
                                        <span v-if="item.group_name" class="text-cafe-600 dark:text-cafe-300">
                                            {{ item.group_name }}
                                        </span>
                                        <span
                                            class="px-1.5 py-0.2 rounded text-[10px] font-medium"
                                            :class="item.status === 'resolved' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300'"
                                        >
                                            {{ item.status === 'resolved' ? t('observations.status_resolved') : t('observations.status_pending') }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-cafe-600 dark:text-cafe-300 mt-0.5 line-clamp-1">
                                        {{ item.content }}
                                    </p>
                                </div>
                                <span class="text-[11px] text-cafe-400 dark:text-cafe-500 shrink-0">
                                    {{ item.created_at }}
                                </span>
                            </div>
                        </div>

                        <p v-else class="text-xs text-cafe-400 dark:text-cafe-500 text-center py-6">
                            {{ t('workspace.no_activity') }}
                        </p>
                    </section>
                </div>

                <!-- ── Right Column (1 col): Insights & Automated Suggestions ── -->
                <div class="space-y-6">
                    <!-- SECTION: Pedagogical Insights & Alerts -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs">
                        <div class="flex items-center gap-2.5 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 flex items-center justify-center shrink-0">
                                <AlertRegular class="w-4 h-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                    {{ t('workspace.insights') }}
                                </h2>
                                <p class="text-[11px] text-cafe-500 dark:text-cafe-400">
                                    {{ t('workspace.insights_subtitle') }}
                                </p>
                            </div>
                        </div>

                        <div v-if="insights && insights.length > 0" class="space-y-3">
                            <a
                                v-for="(insight, idx) in insights"
                                :key="idx"
                                :href="insight.route || '#'"
                                class="block p-3 rounded-xl border-l-4 transition-all hover:shadow-xs"
                                :class="[
                                    insightSeverityClasses[insight.severity]?.border,
                                    insightSeverityClasses[insight.severity]?.bg,
                                ]"
                            >
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span
                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                        :class="insightSeverityClasses[insight.severity]?.badge"
                                    >
                                        {{ t('insights.severity_' + insight.severity) }}
                                    </span>
                                </div>
                                <p class="text-xs font-medium text-cafe-900 dark:text-cafe-100 leading-snug">
                                    {{ insight.message }}
                                </p>
                                <p v-if="insight.suggested_action" class="text-[11px] text-cafe-600 dark:text-cafe-400 mt-1 flex items-center gap-1 font-medium">
                                    <RightSmallRegular class="w-3 h-3 text-accent-500" />
                                    <span>{{ insight.suggested_action }}</span>
                                </p>
                            </a>
                        </div>

                        <div v-else class="text-center py-6 px-3 rounded-xl bg-cafe-50/50 dark:bg-surface-dark-2/40 border border-dashed border-cafe-200/80 dark:border-surface-dark-3">
                            <CheckCircleRegular class="w-6 h-6 text-emerald-500 mx-auto mb-1.5" />
                            <p class="text-xs text-cafe-600 dark:text-cafe-400">
                                {{ t('workspace.no_insights') }}
                            </p>
                        </div>
                    </section>

                    <!-- SECTION: Suggested Automated Actions -->
                    <section v-if="suggestedActions && suggestedActions.length > 0" class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs">
                        <div class="flex items-center gap-2.5 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-accent-50 dark:bg-accent-950/40 text-accent-700 dark:text-accent-400 flex items-center justify-center shrink-0">
                                <SparklesRegular class="w-4 h-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                    {{ t('workspace.suggested_actions') }}
                                </h2>
                                <p class="text-[11px] text-cafe-500 dark:text-cafe-400">
                                    {{ t('automation.suggested_actions') }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2.5">
                            <a
                                v-for="(action, idx) in suggestedActions"
                                :key="idx"
                                :href="action.route ? route(action.route) : '#'"
                                class="flex items-start gap-2.5 p-3 rounded-xl bg-cafe-50/60 dark:bg-surface-dark-2/50 border border-cafe-200/70 dark:border-surface-dark-3 hover:border-accent-300 dark:hover:border-accent-700 hover:shadow-xs transition-all text-left group"
                            >
                                <div class="w-7 h-7 rounded-lg bg-white dark:bg-surface-dark-3 text-accent-600 dark:text-accent-400 flex items-center justify-center shrink-0 mt-0.5 group-hover:scale-105 transition-transform shadow-2xs">
                                    <Book2Regular class="w-4 h-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-cafe-900 dark:text-cafe-100 group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors">
                                        {{ action.title }}
                                    </p>
                                    <p class="text-[11px] text-cafe-500 dark:text-cafe-400 mt-0.5 line-clamp-2">
                                        {{ action.description }}
                                    </p>
                                </div>
                            </a>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- ═══ MODAL: SELECT GROUP FOR ATTENDANCE ═══ -->
        <div
            v-if="showAttendanceModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
            @click.self="showAttendanceModal = false"
        >
            <div class="w-full max-w-md bg-white dark:bg-surface-dark-1 border border-cafe-200 dark:border-surface-dark-3 rounded-2xl p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <CheckCircleRegular class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                {{ t('workspace.select_group_modal_title') }}
                            </h3>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                {{ t('workspace.select_group_modal_desc') }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showAttendanceModal = false"
                        class="text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-300 p-1"
                    >
                        <CloseCircleRegular class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    <button
                        v-for="group in groups"
                        :key="group.id"
                        type="button"
                        @click="router.visit(route('attendance.index', group.id)); showAttendanceModal = false"
                        class="w-full flex items-center justify-between p-3 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/50 hover:bg-emerald-50/50 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-left"
                    >
                        <div>
                            <p class="text-xs font-bold text-cafe-900 dark:text-cafe-100">{{ group.name }}</p>
                            <p v-if="group.subject" class="text-[11px] text-cafe-500 dark:text-cafe-400">{{ group.subject }}</p>
                        </div>
                        <span class="text-xs font-semibold text-cafe-600 dark:text-cafe-300 flex items-center gap-1">
                            <span>{{ group.students_count }}</span>
                            <GroupRegular class="w-3.5 h-3.5 text-cafe-400" />
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ MODAL: SELECT GROUP FOR GRADING ═══ -->
        <div
            v-if="showGradingModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
            @click.self="showGradingModal = false"
        >
            <div class="w-full max-w-md bg-white dark:bg-surface-dark-1 border border-cafe-200 dark:border-surface-dark-3 rounded-2xl p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <AwardRegular class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                {{ t('workspace.select_group_grade_title') }}
                            </h3>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                {{ t('workspace.select_group_grade_desc') }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showGradingModal = false"
                        class="text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-300 p-1"
                    >
                        <CloseCircleRegular class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    <button
                        v-for="group in groups"
                        :key="group.id"
                        type="button"
                        @click="router.visit(route('groups.show', group.slug)); showGradingModal = false"
                        class="w-full flex items-center justify-between p-3 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/50 hover:bg-amber-50/50 hover:border-amber-300 dark:hover:border-amber-700 transition-colors text-left"
                    >
                        <div>
                            <p class="text-xs font-bold text-cafe-900 dark:text-cafe-100">{{ group.name }}</p>
                            <p v-if="group.subject" class="text-[11px] text-cafe-500 dark:text-cafe-400">{{ group.subject }}</p>
                        </div>
                        <span class="text-xs font-semibold text-cafe-600 dark:text-cafe-300 flex items-center gap-1">
                            <span>{{ group.students_count }}</span>
                            <GroupRegular class="w-3.5 h-3.5 text-cafe-400" />
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
