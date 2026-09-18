<script setup lang="ts">
/**
 * Groups Index — Pedagogical Academic Groups Manager
 *
 * Restful gradient backdrop, smart sorting prioritizing active periods,
 * multi-criteria filtering, search, and instant group relocation between periods.
 */

import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AddCircleRegular,
    Book2Regular,
    CalendarMonthRegular,
    CheckCircleRegular,
    CloseCircleRegular,
    EyeRegular,
    GroupRegular,
    RightSmallRegular,
    Search2Regular,
    TransferRegular,
    User4Regular,
} from '@mingcute/vue/core-regular';
import { computed, ref } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpSelect from '@/Components/CpSelect.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface PeriodInfo {
    id: number;
    name: string;
    slug?: string;
    is_active?: boolean;
}

interface GroupItem {
    id: number;
    name: string;
    slug: string;
    subject: string | null;
    educational_level: string | null;
    period_id: number;
    period?: PeriodInfo | null;
    students_count: number;
    is_archived: boolean;
}

interface Props {
    groups: GroupItem[];
    periods: PeriodInfo[];
    activePeriodId?: number | null;
}

const props = withDefaults(defineProps<Props>(), {
    groups: () => [],
    periods: () => [],
    activePeriodId: null,
});

const { t } = useTranslations();

// Filters state
const activeTab = ref<'active_period' | 'all' | 'archived'>('active_period');
const selectedPeriodFilter = ref<string>('all');
const searchQuery = ref('');

// Move Period Modal state
const movingGroup = ref<GroupItem | null>(null);
const moveForm = useForm({
    period_id: '',
});

function openMoveModal(group: GroupItem) {
    movingGroup.value = group;
    moveForm.period_id = String(group.period_id);
}

function closeMoveModal() {
    movingGroup.value = null;
    moveForm.reset();
}

function submitMovePeriod() {
    if (!movingGroup.value) return;

    moveForm.patch(route('groups.move-period', movingGroup.value.slug), {
        preserveScroll: true,
        onSuccess: () => closeMoveModal(),
    });
}

// Period options for select
const periodOptions = computed(() => [
    { value: 'all', label: t('groups.filter_all') },
    ...props.periods.map((p) => ({
        value: String(p.id),
        label: `${p.name} ${p.is_active ? `(${t('groups.active')})` : ''}`,
    })),
]);

const modalPeriodOptions = computed(() =>
    props.periods.map((p) => ({
        value: String(p.id),
        label: `${p.name} ${p.is_active ? `(${t('groups.active')})` : ''}`,
    })),
);

// Filtered and smart sorted groups
const filteredGroups = computed(() => {
    let result = [...props.groups];

    // Tab filter
    if (activeTab.value === 'active_period') {
        if (props.activePeriodId) {
            result = result.filter((g) => g.period_id === props.activePeriodId && !g.is_archived);
        } else {
            result = result.filter((g) => !g.is_archived);
        }
    } else if (activeTab.value === 'archived') {
        result = result.filter((g) => g.is_archived);
    } else {
        result = result.filter((g) => !g.is_archived);
    }

    // Period dropdown filter
    if (selectedPeriodFilter.value !== 'all') {
        const pId = Number(selectedPeriodFilter.value);
        result = result.filter((g) => g.period_id === pId);
    }

    // Search query filter
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        result = result.filter(
            (g) =>
                g.name.toLowerCase().includes(q) ||
                Boolean(g.subject?.toLowerCase().includes(q)) ||
                Boolean(g.educational_level?.toLowerCase().includes(q)) ||
                Boolean(g.period?.name.toLowerCase().includes(q)),
        );
    }

    return result;
});

const counts = computed(() => ({
    activePeriod: props.groups.filter((g) => g.period_id === props.activePeriodId && !g.is_archived)
        .length,
    all: props.groups.filter((g) => !g.is_archived).length,
    archived: props.groups.filter((g) => g.is_archived).length,
}));
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('groups.title')" />

        <div class="space-y-6">
            <!-- Header card with restful pedagogical gradient -->
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                            <GroupRegular class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                                {{ t('groups.title') }}
                            </h1>
                            <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5 leading-relaxed">
                                {{ t('groups.subtitle') }}
                            </p>
                        </div>
                    </div>

                    <Link :href="route('groups.create')" class="shrink-0">
                        <CpButton type="button" class="inline-flex items-center gap-2 text-sm shadow-sm">
                            <AddCircleRegular class="w-4 h-4" />
                            <span>{{ t('groups.create') }}</span>
                        </CpButton>
                    </Link>
                </div>

                <!-- Ergonomic filters & search bar -->
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3.5 mt-5 pt-4 border-t border-cafe-200/60 dark:border-cafe-800/60">
                    <!-- Tab Pills -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            @click="activeTab = 'active_period'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                            :class="activeTab === 'active_period'
                                ? 'bg-state-success text-white shadow-sm font-semibold'
                                : 'text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200/50 dark:hover:bg-surface-dark-2'"
                        >
                            {{ t('groups.filter_active_period') }} ({{ counts.activePeriod }})
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'all'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                            :class="activeTab === 'all'
                                ? 'bg-cafe-800 text-white dark:bg-cafe-100 dark:text-cafe-900 shadow-sm'
                                : 'text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200/50 dark:hover:bg-surface-dark-2'"
                        >
                            {{ t('groups.filter_all') }} ({{ counts.all }})
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'archived'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                            :class="activeTab === 'archived'
                                ? 'bg-cafe-600 text-white dark:bg-surface-dark-3 shadow-sm'
                                : 'text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200/50 dark:hover:bg-surface-dark-2'"
                        >
                            {{ t('groups.filter_archived') }} ({{ counts.archived }})
                        </button>
                    </div>

                    <!-- Search and Period Selector -->
                    <div class="flex items-center gap-2">
                        <div class="relative w-full sm:w-64">
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="t('groups.search_placeholder')"
                                class="cp-input text-xs py-1.5 pl-8 pr-3"
                            />
                            <Search2Regular class="w-3.5 h-3.5 text-cafe-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="filteredGroups.length === 0"
                class="rounded-2xl border border-dashed border-cafe-300 dark:border-cafe-700 bg-white/50 dark:bg-surface-dark-1/50 p-12 text-center"
            >
                <div class="mx-auto w-12 h-12 rounded-2xl bg-accent-50 dark:bg-surface-dark-2 flex items-center justify-center text-accent-600 dark:text-accent-300 mb-3.5">
                    <GroupRegular class="w-6 h-6" />
                </div>
                <h3 class="text-base font-serif font-bold text-cafe-800 dark:text-cafe-100 mb-1">
                    {{ t('groups.empty') }}
                </h3>
                <p class="text-xs text-cafe-500 dark:text-cafe-400 max-w-sm mx-auto mb-4 leading-relaxed">
                    {{ t('groups.subtitle') }}
                </p>
                <Link :href="route('groups.create')">
                    <CpButton type="button" class="inline-flex items-center gap-2 text-xs">
                        <AddCircleRegular class="w-3.5 h-3.5" />
                        <span>{{ t('groups.create') }}</span>
                    </CpButton>
                </Link>
            </div>

            <!-- Aesthetic Table Container -->
            <div v-else class="overflow-hidden rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-cafe-200/80 dark:border-cafe-800/90 bg-cafe-100/50 dark:bg-surface-dark-2/60 text-xs font-semibold uppercase tracking-wider text-cafe-600 dark:text-cafe-300">
                            <th class="py-3 px-5">
                                <span class="inline-flex items-center gap-1.5">
                                    <GroupRegular class="w-3.5 h-3.5 opacity-70" />
                                    {{ t('groups.name') }}
                                </span>
                            </th>
                            <th class="py-3 px-5">
                                <span class="inline-flex items-center gap-1.5">
                                    <Book2Regular class="w-3.5 h-3.5 opacity-70" />
                                    {{ t('groups.subject') }}
                                </span>
                            </th>
                            <th class="py-3 px-5">
                                <span class="inline-flex items-center gap-1.5">
                                    <CalendarMonthRegular class="w-3.5 h-3.5 opacity-70" />
                                    {{ t('groups.period') }}
                                </span>
                            </th>
                            <th class="py-3 px-5 text-center">
                                <span class="inline-flex items-center justify-center gap-1.5">
                                    <User4Regular class="w-3.5 h-3.5 opacity-70" />
                                    {{ t('groups.students_count') }}
                                </span>
                            </th>
                            <th class="py-3 px-5 text-center">
                                <span class="inline-flex items-center justify-center gap-1.5">
                                    <CheckCircleRegular class="w-3.5 h-3.5 opacity-70" />
                                    {{ t('groups.status') }}
                                </span>
                            </th>
                            <th class="py-3 px-5 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cafe-200/60 dark:divide-cafe-800/60">
                        <tr
                            v-for="group in filteredGroups"
                            :key="group.id"
                            class="hover:bg-cafe-50/70 dark:hover:bg-surface-dark-2/40 transition-colors group"
                            :class="{ 'bg-state-success/5': group.period_id === activePeriodId && !group.is_archived }"
                        >
                            <!-- Group Name -->
                            <td class="py-3.5 px-5">
                                <Link
                                    :href="route('groups.show', group.slug)"
                                    class="font-semibold text-cafe-900 dark:text-cafe-100 group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors inline-flex items-center gap-2.5"
                                >
                                    <span
                                        class="w-2.5 h-2.5 rounded-full shrink-0"
                                        :class="group.period_id === activePeriodId && !group.is_archived ? 'bg-state-success ring-4 ring-state-success/20' : 'bg-cafe-300 dark:bg-cafe-600'"
                                        :title="group.period_id === activePeriodId ? t('groups.active_period_badge') : ''"
                                    />
                                    <span>{{ group.name }}</span>
                                </Link>
                            </td>

                            <!-- Subject & Level -->
                            <td class="py-3.5 px-5 text-xs text-cafe-600 dark:text-cafe-300">
                                <div>
                                    <span class="font-medium text-cafe-800 dark:text-cafe-200">{{ group.subject || '—' }}</span>
                                    <span v-if="group.educational_level" class="block text-[11px] text-cafe-500 dark:text-cafe-400 mt-0.5">
                                        {{ group.educational_level }}
                                    </span>
                                </div>
                            </td>

                            <!-- Period with Move action -->
                            <td class="py-3.5 px-5 text-xs">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-medium"
                                        :class="group.period_id === activePeriodId ? 'text-state-success font-semibold' : 'text-cafe-700 dark:text-cafe-300'"
                                    >
                                        {{ group.period?.name || '—' }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="openMoveModal(group)"
                                        class="p-1 rounded text-cafe-400 hover:text-accent-600 dark:hover:text-accent-300 hover:bg-cafe-100 dark:hover:bg-surface-dark-2 transition-colors"
                                        :title="t('groups.move_period')"
                                    >
                                        <TransferRegular class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>

                            <!-- Students Count -->
                            <td class="py-3.5 px-5 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-cafe-100 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300">
                                    <User4Regular class="w-3.5 h-3.5 opacity-70" />
                                    <span>{{ group.students_count }}</span>
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-5 text-center">
                                <span
                                    v-if="group.is_archived"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-medium bg-cafe-100 dark:bg-surface-dark-2 text-cafe-500 dark:text-cafe-400"
                                >
                                    <CloseCircleRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('groups.archived') }}</span>
                                </span>
                                <span
                                    v-else-if="group.period_id === activePeriodId"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-medium bg-state-success/15 text-state-success border border-state-success/30"
                                >
                                    <CheckCircleRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('groups.active_period_badge') }}</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-medium bg-cafe-100 dark:bg-surface-dark-2 text-cafe-600 dark:text-cafe-300"
                                >
                                    <span>{{ t('groups.active') }}</span>
                                </span>
                            </td>

                            <!-- Action link -->
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="route('groups.show', group.slug)"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-cafe-500 hover:text-accent-600 dark:text-cafe-400 dark:hover:text-accent-300 transition-colors p-1.5 rounded-md hover:bg-cafe-100 dark:hover:bg-surface-dark-2"
                                    >
                                        <EyeRegular class="w-4 h-4" />
                                        <RightSmallRegular class="w-4 h-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modal: Move Group to another period -->
            <div
                v-if="movingGroup"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
                @click.self="closeMoveModal"
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

                    <div class="p-3 rounded-xl bg-cafe-50 dark:bg-surface-dark-2 border border-cafe-200/80 dark:border-cafe-800 text-xs">
                        <span class="font-semibold text-cafe-800 dark:text-cafe-200">{{ movingGroup.name }}</span>
                        <span class="text-cafe-500 dark:text-cafe-400 block mt-0.5">
                            {{ t('groups.period') }}: {{ movingGroup.period?.name }}
                        </span>
                    </div>

                    <form @submit.prevent="submitMovePeriod" class="space-y-4 pt-1">
                        <div>
                            <label class="cp-label text-xs font-semibold">{{ t('groups.select_period') }}</label>
                            <CpSelect
                                id="move_period_id"
                                v-model="moveForm.period_id"
                                :options="modalPeriodOptions"
                                required
                            />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <CpButton type="button" variant="ghost" @click="closeMoveModal">
                                {{ t('common.cancel') }}
                            </CpButton>
                            <CpButton type="submit" :loading="moveForm.processing" :disabled="moveForm.processing">
                                {{ t('groups.move_period') }}
                            </CpButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
