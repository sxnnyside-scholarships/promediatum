<script setup lang="ts">
/**
 * Students Index — Pedagogical Student Directory & Scan Workspace
 *
 * Fully aligned with the Café Pedagógico Design System:
 * rounded-2xl cards, restful pedagogical gradient, unified warm palettes,
 * initials pseudo-avatars, search, filters, and seamless table/card view switching.
 */

import { Head, Link } from '@inertiajs/vue3';
import {
    AddCircleRegular,
    AlertRegular,
    CheckCircleRegular,
    GridRegular,
    GroupRegular,
    MailRegular,
    PhoneRegular,
    RightSmallRegular,
    Search2Regular,
    TableRegular,
    User4Regular,
    UserAddRegular,
} from '@mingcute/vue/core-regular';
import { computed, ref } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface GroupInfo {
    id: number;
    name: string;
    period?: {
        id: number;
        name: string;
        is_active?: boolean;
    };
}

interface StudentItem {
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

interface StudentMetrics {
    average: number | null;
    at_risk: boolean;
    has_absence_alert: boolean;
    groups_count: number;
}

const props = defineProps<{
    students: StudentItem[];
    groups?: GroupInfo[];
    metrics?: Record<number, StudentMetrics>;
}>();

const { t } = useTranslations();

// Filters & View state
const searchQuery = ref('');
const selectedGroupFilter = ref<string>('all');
const selectedStatusFilter = ref<'all' | 'alert' | 'normal'>('all');
const viewMode = ref<'table' | 'grid'>('table');

// Computed filtered list
const filteredStudents = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    const groupFilter = selectedGroupFilter.value;
    const statusFilter = selectedStatusFilter.value;

    return props.students.filter((student) => {
        // Text match
        if (q) {
            const matchesName = student.full_name?.toLowerCase().includes(q);
            const matchesEmail = student.email?.toLowerCase().includes(q);
            const matchesGuardian = student.guardian_name?.toLowerCase().includes(q);
            const matchesGroups = student.groups?.some((g) => g.name?.toLowerCase().includes(q));

            if (!matchesName && !matchesEmail && !matchesGuardian && !matchesGroups) {
                return false;
            }
        }

        // Group filter
        if (groupFilter !== 'all') {
            const gid = Number(groupFilter);
            const hasGroup = student.groups?.some((g) => g.id === gid);
            if (!hasGroup) {
                return false;
            }
        }

        // Status filter
        if (statusFilter !== 'all' && props.metrics) {
            const metric = props.metrics[student.id];
            const isAlert = metric?.at_risk || metric?.has_absence_alert;
            if (statusFilter === 'alert' && !isAlert) {
                return false;
            }
            if (statusFilter === 'normal' && isAlert) {
                return false;
            }
        }

        return true;
    });
});

// Summary numbers
const totalStudentsCount = computed(() => props.students.length);
const atRiskCount = computed(() => {
    if (!props.metrics) return 0;
    return props.students.filter((s) => {
        const m = props.metrics?.[s.id];
        return m?.at_risk || m?.has_absence_alert;
    }).length;
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('students.title')" />

        <div class="space-y-6 max-w-7xl">
            <!-- Header card with restful pedagogical gradient -->
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                            <User4Regular class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                                {{ t('students.title') }}
                            </h1>
                            <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5 leading-relaxed">
                                {{ t('students.subtitle') }}
                            </p>
                        </div>
                    </div>

                    <Link :href="route('students.create')" class="shrink-0">
                        <CpButton type="button" class="inline-flex items-center gap-2 text-sm shadow-sm">
                            <UserAddRegular class="w-4 h-4" />
                            <span>{{ t('students.create') }}</span>
                        </CpButton>
                    </Link>
                </div>

                <!-- Metric Quick Highlights -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-6 pt-5 border-t border-cafe-200/60 dark:border-cafe-800/60">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/90 dark:bg-surface-dark-3 flex items-center justify-center border border-cafe-200/80 dark:border-cafe-700 text-cafe-800 dark:text-cafe-100 shadow-2xs">
                            <span class="font-bold text-base font-serif">{{ totalStudentsCount }}</span>
                        </div>
                        <div>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">{{ t('students.title') }}</p>
                            <p class="text-xs text-cafe-800 dark:text-cafe-200 font-semibold">{{ totalStudentsCount }} {{ t('common.total') }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/90 dark:bg-surface-dark-3 flex items-center justify-center border border-cafe-200/80 dark:border-cafe-700 text-cafe-700 dark:text-cafe-200 shadow-2xs">
                            <GroupRegular class="w-5 h-5 text-accent-600 dark:text-accent-400" />
                        </div>
                        <div>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">{{ t('groups.title') }}</p>
                            <p class="text-xs text-cafe-800 dark:text-cafe-200 font-semibold">{{ groups?.length || 0 }} {{ t('common.active') }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 col-span-2 sm:col-span-1">
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center border shadow-2xs"
                            :class="atRiskCount > 0 ? 'bg-state-warning/15 border-state-warning/30 text-state-warning' : 'bg-state-success/15 border-state-success/30 text-state-success'"
                        >
                            <AlertRegular v-if="atRiskCount > 0" class="w-5 h-5" />
                            <CheckCircleRegular v-else class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">{{ t('students.filter_status') }}</p>
                            <p
                                class="text-xs font-bold"
                                :class="atRiskCount > 0 ? 'text-state-warning' : 'text-state-success'"
                            >
                                {{ atRiskCount > 0 ? `${atRiskCount} ${t('students.status_alert')}` : t('students.status_normal') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Controls: Search, Filters, View Mode Toggle -->
            <div class="bg-white dark:bg-surface-dark-1 rounded-2xl border border-cafe-200 dark:border-cafe-800 p-4 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[220px]">
                        <Search2Regular class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-cafe-400 pointer-events-none" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="t('students.search_placeholder')"
                            class="w-full pl-9 pr-3 py-2 text-xs bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500 transition-colors"
                        />
                    </div>

                    <!-- Group Selector Filter -->
                    <div v-if="groups?.length" class="min-w-[180px]">
                        <select
                            v-model="selectedGroupFilter"
                            class="w-full px-3 py-2 text-xs bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-800 dark:text-cafe-200 focus:outline-none focus:ring-1 focus:ring-accent-500 transition-colors"
                        >
                            <option value="all">{{ t('students.all_groups') }}</option>
                            <option v-for="grp in groups" :key="grp.id" :value="String(grp.id)">
                                {{ grp.name }} {{ grp.period ? `(${grp.period.name})` : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="min-w-[160px]">
                        <select
                            v-model="selectedStatusFilter"
                            class="w-full px-3 py-2 text-xs bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-800 dark:text-cafe-200 focus:outline-none focus:ring-1 focus:ring-accent-500 transition-colors"
                        >
                            <option value="all">{{ t('students.all_statuses') }}</option>
                            <option value="normal">{{ t('students.status_normal') }}</option>
                            <option value="alert">{{ t('students.status_alert') }}</option>
                        </select>
                    </div>
                </div>

                <!-- View Mode Switcher -->
                <div class="flex items-center gap-1 self-end lg:self-auto border border-cafe-200 dark:border-cafe-700 rounded-lg p-0.5 bg-cafe-100/70 dark:bg-surface-dark-2">
                    <button
                        type="button"
                        :title="t('students.view_mode_table')"
                        :class="[
                            'px-2.5 py-1.5 rounded-md text-xs transition-colors flex items-center gap-1.5 font-medium',
                            viewMode === 'table'
                                ? 'bg-white dark:bg-surface-dark-3 text-accent-700 dark:text-accent-300 shadow-2xs font-semibold'
                                : 'text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200',
                        ]"
                        @click="viewMode = 'table'"
                    >
                        <TableRegular class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">{{ t('students.view_mode_table') }}</span>
                    </button>
                    <button
                        type="button"
                        :title="t('students.view_mode_grid')"
                        :class="[
                            'px-2.5 py-1.5 rounded-md text-xs transition-colors flex items-center gap-1.5 font-medium',
                            viewMode === 'grid'
                                ? 'bg-white dark:bg-surface-dark-3 text-accent-700 dark:text-accent-300 shadow-2xs font-semibold'
                                : 'text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200',
                        ]"
                        @click="viewMode = 'grid'"
                    >
                        <GridRegular class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">{{ t('students.view_mode_grid') }}</span>
                    </button>
                </div>
            </div>

            <!-- Empty State: No students registered at all -->
            <div
                v-if="students.length === 0"
                class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 py-16 text-center space-y-4 shadow-sm"
            >
                <div class="w-16 h-16 mx-auto rounded-2xl bg-cafe-100 dark:bg-surface-dark-2 flex items-center justify-center text-cafe-500 dark:text-cafe-400 border border-cafe-200/60 dark:border-cafe-700">
                    <User4Regular class="w-8 h-8" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-serif text-lg text-cafe-900 dark:text-cafe-100 font-bold">
                        {{ t('students.empty') }}
                    </h3>
                    <p class="text-xs text-cafe-500 dark:text-cafe-400 max-w-sm mx-auto">
                        {{ t('students.tip_1') }}
                    </p>
                </div>
                <Link :href="route('students.create')">
                    <CpButton type="button" class="inline-flex items-center gap-2 text-sm shadow-sm">
                        <UserAddRegular class="w-4 h-4" />
                        <span>{{ t('students.create') }}</span>
                    </CpButton>
                </Link>
            </div>

            <!-- Empty State: Filter mismatch -->
            <div
                v-else-if="filteredStudents.length === 0"
                class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 py-12 text-center space-y-3 shadow-sm"
            >
                <p class="text-xs text-cafe-600 dark:text-cafe-300 font-medium">
                    {{ t('common.no_results') }}
                </p>
                <button
                    type="button"
                    class="text-xs text-accent-600 dark:text-accent-400 hover:underline font-semibold"
                    @click="searchQuery = ''; selectedGroupFilter = 'all'; selectedStatusFilter = 'all'"
                >
                    {{ t('common.reset_filters') }}
                </button>
            </div>

            <!-- Table View Mode -->
            <div
                v-else-if="viewMode === 'table'"
                class="overflow-hidden rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-scan-body text-left border-collapse">
                        <thead>
                            <tr class="bg-cafe-100/70 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-200 border-b border-cafe-200 dark:border-cafe-800">
                                <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider">{{ t('students.name') }}</th>
                                <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider">{{ t('students.groups') }}</th>
                                <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider">{{ t('students.contact_info') }}</th>
                                <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider text-center">{{ t('grades.score') }}</th>
                                <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider text-center">{{ t('students.risk') }}</th>
                                <th class="px-4 py-3.5 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cafe-100 dark:divide-cafe-800/60">
                            <tr
                                v-for="student in filteredStudents"
                                :key="student.id"
                                class="hover:bg-cafe-50/70 dark:hover:bg-surface-dark-2/60 transition-colors"
                            >
                                <!-- Name with StudentAvatar -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <StudentAvatar :student="student" size="sm" />
                                        <div>
                                            <Link
                                                :href="route('students.show', student.slug)"
                                                class="font-medium text-cafe-900 dark:text-cafe-100 hover:text-accent-600 dark:hover:text-accent-400 transition-colors"
                                            >
                                                {{ student.full_name }}
                                            </Link>
                                            <p v-if="student.guardian_name" class="text-[11px] text-cafe-500 dark:text-cafe-400">
                                                {{ student.guardian_name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Groups -->
                                <td class="px-4 py-3 text-cafe-600 dark:text-cafe-300 text-xs">
                                    <div v-if="student.groups?.length" class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="grp in student.groups"
                                            :key="grp.id"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs bg-cafe-100 dark:bg-surface-dark-3 text-cafe-700 dark:text-cafe-300 font-medium"
                                        >
                                            {{ grp.name }}
                                        </span>
                                    </div>
                                    <span v-else class="text-cafe-400 dark:text-cafe-500">—</span>
                                </td>

                                <!-- Contact Info -->
                                <td class="px-4 py-3 text-xs text-cafe-600 dark:text-cafe-400">
                                    <div class="flex items-center gap-3">
                                        <span v-if="student.email" class="inline-flex items-center gap-1 text-cafe-600 dark:text-cafe-300" :title="student.email">
                                            <MailRegular class="w-3.5 h-3.5 text-cafe-400" />
                                            <span class="max-w-[130px] truncate">{{ student.email }}</span>
                                        </span>
                                        <span v-if="student.phone" class="inline-flex items-center gap-1 text-cafe-600 dark:text-cafe-300" :title="student.phone">
                                            <PhoneRegular class="w-3.5 h-3.5 text-cafe-400" />
                                            <span>{{ student.phone }}</span>
                                        </span>
                                        <span v-if="!student.email && !student.phone" class="text-cafe-400 dark:text-cafe-500">—</span>
                                    </div>
                                </td>

                                <!-- Score -->
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="metrics?.[student.id]?.average !== null && metrics?.[student.id]?.average !== undefined"
                                        :class="[
                                            'font-semibold text-xs px-2.5 py-0.5 rounded-lg',
                                            (metrics[student.id].average ?? 0) < 60
                                                ? 'bg-state-danger/15 text-state-danger'
                                                : (metrics[student.id].average ?? 0) < 70
                                                  ? 'bg-state-warning/15 text-state-warning'
                                                  : 'bg-state-success/15 text-state-success',
                                        ]"
                                    >
                                        {{ metrics[student.id].average }}%
                                    </span>
                                    <span v-else class="text-xs text-cafe-400">—</span>
                                </td>

                                <!-- Risk / Alert Status -->
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="metrics?.[student.id]?.at_risk || metrics?.[student.id]?.has_absence_alert"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs bg-state-warning/15 text-state-warning font-bold"
                                    >
                                        <AlertRegular class="w-3 h-3" />
                                        <span>{{ t('students.at_risk') }}</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs bg-cafe-100 dark:bg-surface-dark-3 text-cafe-600 dark:text-cafe-400"
                                    >
                                        {{ t('students.status_normal') }}
                                    </span>
                                </td>

                                <!-- Profile Link -->
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('students.show', student.slug)"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-cafe-500 hover:text-accent-600 hover:bg-cafe-100 dark:hover:bg-surface-dark-3 transition-colors"
                                        :title="t('students.title')"
                                    >
                                        <RightSmallRegular class="w-4 h-4" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Grid Cards View Mode -->
            <div
                v-else
                class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"
            >
                <div
                    v-for="student in filteredStudents"
                    :key="student.id"
                    class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 p-5 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between space-y-4"
                >
                    <div class="space-y-4">
                        <!-- Top Header: Avatar, Name & Risk Status -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <StudentAvatar :student="student" size="md" />
                                <div class="min-w-0">
                                    <Link
                                        :href="route('students.show', student.slug)"
                                        class="font-medium text-base text-cafe-900 dark:text-cafe-50 hover:text-accent-600 dark:hover:text-accent-400 transition-colors block truncate"
                                    >
                                        {{ student.full_name }}
                                    </Link>
                                    <p v-if="student.guardian_name" class="text-xs text-cafe-500 dark:text-cafe-400 truncate">
                                        {{ student.guardian_name }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="metrics?.[student.id]?.at_risk || metrics?.[student.id]?.has_absence_alert">
                                <span class="inline-flex items-center gap-1 p-1.5 rounded-lg bg-state-warning/15 text-state-warning font-semibold" :title="t('students.at_risk')">
                                    <AlertRegular class="w-4 h-4" />
                                </span>
                            </div>
                        </div>

                        <!-- Groups Tag list -->
                        <div>
                            <p class="text-[11px] uppercase tracking-wider text-cafe-400 dark:text-cafe-500 font-bold mb-1.5">{{ t('students.groups') }}</p>
                            <div v-if="student.groups?.length" class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="grp in student.groups"
                                    :key="grp.id"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs bg-cafe-100 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300 font-medium"
                                >
                                    {{ grp.name }}
                                </span>
                            </div>
                            <span v-else class="text-xs text-cafe-400">—</span>
                        </div>

                        <!-- Contact Data snippet -->
                        <div class="space-y-1 text-xs text-cafe-600 dark:text-cafe-400 pt-2 border-t border-cafe-100 dark:border-cafe-800">
                            <div v-if="student.email" class="flex items-center gap-2 truncate">
                                <MailRegular class="w-3.5 h-3.5 text-cafe-400 shrink-0" />
                                <span class="truncate">{{ student.email }}</span>
                            </div>
                            <div v-if="student.phone" class="flex items-center gap-2 truncate">
                                <PhoneRegular class="w-3.5 h-3.5 text-cafe-400 shrink-0" />
                                <span>{{ student.phone }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer: Average & Action -->
                    <div class="pt-3 border-t border-cafe-100 dark:border-cafe-800 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] text-cafe-400 block uppercase font-bold">{{ t('grades.score') }}</span>
                            <span
                                v-if="metrics?.[student.id]?.average !== null && metrics?.[student.id]?.average !== undefined"
                                :class="[
                                    'font-bold text-sm',
                                    (metrics[student.id].average ?? 0) < 60
                                        ? 'text-state-danger'
                                        : (metrics[student.id].average ?? 0) < 70
                                          ? 'text-state-warning'
                                          : 'text-state-success',
                                ]"
                            >
                                {{ metrics[student.id].average }}%
                            </span>
                            <span v-else class="text-xs text-cafe-400">—</span>
                        </div>

                        <Link
                            :href="route('students.show', student.slug)"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-accent-600 dark:text-accent-400 hover:underline"
                        >
                            <span>{{ t('common.details') }}</span>
                            <RightSmallRegular class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
