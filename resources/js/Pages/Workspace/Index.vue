<script setup>
/**
 * Workspace Index — /workspace
 * Bento grid layout with 5 sections:
 * 1. Active Period Summary
 * 2. Groups quick access
 * 3. Pending Observations
 * 4. Recent Activity
 * 5. Favorites placeholder
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpIcon from '@/Components/CpIcon.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link } from '@inertiajs/vue3';

const { t } = useTranslations();

const props = defineProps({
    activePeriod: { type: Object, default: null },
    groups: { type: Array, default: () => [] },
    pendingObservations: { type: Array, default: () => [] },
    recentActivity: { type: Array, default: () => [] },
    insights: { type: Array, default: () => [] },
    suggestedActions: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const insightSeverityStyle = {
    critical: 'border-l-state-danger bg-state-danger/5 dark:bg-state-danger/10',
    high: 'border-l-state-warning bg-state-warning/5 dark:bg-state-warning/10',
    medium: 'border-l-accent-400 bg-accent-50/50 dark:bg-accent-900/10',
    low: 'border-l-state-info bg-state-info/5 dark:bg-state-info/10',
};

const insightSeverityDot = {
    critical: 'bg-state-danger',
    high: 'bg-state-warning',
    medium: 'bg-accent-400',
    low: 'bg-state-info',
};

const actionSeverityStyle = {
    critical: 'border-l-state-danger',
    high: 'border-l-state-warning',
    medium: 'border-l-accent-400',
    low: 'border-l-state-info',
};

const actionIconMap = {
    book: 'book',
    phone: 'phone',
    download: 'download',
    clipboard: 'clipboard',
};

const observationTypeColor = {
    performance: 'text-state-warning',
    behavior: 'text-state-danger',
    achievement: 'text-state-success',
    followup: 'text-state-info',
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('workspace.title')" />

        <div>
            <h1 class="font-serif mb-8">{{ t('workspace.title') }}</h1>

            <!-- Bento grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ═══ 1. ACTIVE PERIOD ═══ -->
                <section class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <CpIcon name="calendar" :size="18" class-name="text-cafe-500 dark:text-cafe-400" />
                        <h2 class="text-sm font-semibold text-cafe-700 dark:text-cafe-200">
                            {{ t('workspace.active_period') }}
                        </h2>
                    </div>

                    <template v-if="activePeriod">
                        <Link
                            :href="route('periods.show', activePeriod.slug)"
                            class="block group"
                        >
                            <p class="text-lg font-medium text-cafe-800 dark:text-cafe-100 group-hover:text-accent-500 transition-colors duration-150">
                                {{ activePeriod.name }}
                            </p>
                            <p class="text-sm text-cafe-500 dark:text-cafe-400 mt-1">
                                {{ activePeriod.start_date }} → {{ activePeriod.end_date }}
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <CpIcon name="clock" :size="14" class-name="text-cafe-400" />
                                <span class="text-sm font-medium text-accent-500">
                                    {{ activePeriod.days_remaining }} {{ t('periods.days_remaining').toLowerCase() }}
                                </span>
                            </div>
                        </Link>
                    </template>
                    <template v-else>
                        <p class="text-sm text-cafe-400 dark:text-cafe-500">
                            {{ t('workspace.no_active_period') }}
                        </p>
                        <Link
                            :href="route('periods.create')"
                            class="inline-flex items-center gap-1.5 mt-3 text-sm text-accent-500 hover:text-accent-600 transition-colors duration-150"
                        >
                            <CpIcon name="plus" :size="14" />
                            {{ t('periods.create') }}
                        </Link>
                    </template>

                    <!-- Quick stats row -->
                    <div class="mt-5 pt-4 border-t border-cafe-200 dark:border-cafe-700 grid grid-cols-3 gap-3 text-center">
                        <div>
                            <p class="text-lg font-semibold text-cafe-800 dark:text-cafe-100">{{ stats.total_groups }}</p>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400">{{ t('nav.groups') }}</p>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-cafe-800 dark:text-cafe-100">{{ stats.total_students }}</p>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400">{{ t('nav.students') }}</p>
                        </div>
                        <div>
                            <p class="text-lg font-semibold" :class="stats.pending_observations > 0 ? 'text-state-warning' : 'text-cafe-800 dark:text-cafe-100'">
                                {{ stats.pending_observations }}
                            </p>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400">{{ t('workspace.pending') }}</p>
                        </div>
                    </div>
                </section>

                <!-- ═══ 2. GROUPS QUICK ACCESS ═══ -->
                <section class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <CpIcon name="users" :size="18" class-name="text-cafe-500 dark:text-cafe-400" />
                            <h2 class="text-sm font-semibold text-cafe-700 dark:text-cafe-200">
                                {{ t('nav.groups') }}
                            </h2>
                        </div>
                        <Link
                            :href="route('groups.index')"
                            class="text-xs text-accent-500 hover:text-accent-600 transition-colors duration-150"
                        >
                            {{ t('workspace.view_all') }}
                        </Link>
                    </div>

                    <div v-if="groups.length" class="space-y-2">
                        <Link
                            v-for="group in groups"
                            :key="group.id"
                            :href="route('groups.show', group.slug)"
                            class="flex items-center justify-between px-3 py-2.5 rounded-subtle hover:bg-cafe-200/60 dark:hover:bg-surface-dark-2 transition-colors duration-150"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-cafe-800 dark:text-cafe-100 truncate">
                                    {{ group.name }}
                                </p>
                                <p v-if="group.subject" class="text-xs text-cafe-500 dark:text-cafe-400 truncate">
                                    {{ group.subject }}
                                </p>
                            </div>
                            <span class="shrink-0 ml-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 bg-cafe-200/60 dark:bg-surface-dark-2 px-2 py-0.5 rounded-full">
                                {{ group.students_count }}
                            </span>
                        </Link>
                    </div>
                    <div v-else class="text-center py-6">
                        <p class="text-sm text-cafe-400 dark:text-cafe-500">{{ t('groups.empty') }}</p>
                        <Link
                            :href="route('groups.create')"
                            class="inline-flex items-center gap-1.5 mt-3 text-sm text-accent-500 hover:text-accent-600 transition-colors duration-150"
                        >
                            <CpIcon name="plus" :size="14" />
                            {{ t('groups.create') }}
                        </Link>
                    </div>
                </section>

                <!-- ═══ 3. PENDING OBSERVATIONS ═══ -->
                <section class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <CpIcon name="clipboard" :size="18" class-name="text-cafe-500 dark:text-cafe-400" />
                            <h2 class="text-sm font-semibold text-cafe-700 dark:text-cafe-200">
                                {{ t('workspace.pending_observations') }}
                            </h2>
                        </div>
                        <Link
                            :href="route('observations.index')"
                            class="text-xs text-accent-500 hover:text-accent-600 transition-colors duration-150"
                        >
                            {{ t('workspace.view_all') }}
                        </Link>
                    </div>

                    <div v-if="pendingObservations.length" class="space-y-3">
                        <div
                            v-for="obs in pendingObservations"
                            :key="obs.id"
                            class="px-3 py-2.5 rounded-subtle bg-cafe-50 dark:bg-surface-dark-2"
                        >
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-medium" :class="observationTypeColor[obs.type] || 'text-cafe-500'">
                                    {{ t('observations.type_' + obs.type) }}
                                </span>
                                <span class="text-xs text-cafe-400 dark:text-cafe-500">·</span>
                                <span class="text-xs text-cafe-400 dark:text-cafe-500">{{ obs.created_at }}</span>
                            </div>
                            <p class="text-sm text-cafe-700 dark:text-cafe-200">{{ obs.content }}</p>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-1">
                                {{ obs.student_name }} · {{ obs.group_name }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-cafe-400 dark:text-cafe-500 text-center py-6">
                        {{ t('workspace.no_pending') }}
                    </p>
                </section>

                <!-- ═══ 4. RECENT ACTIVITY ═══ -->
                <section class="lg:col-span-2 rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <CpIcon name="activity" :size="18" class-name="text-cafe-500 dark:text-cafe-400" />
                        <h2 class="text-sm font-semibold text-cafe-700 dark:text-cafe-200">
                            {{ t('workspace.recent_activity') }}
                        </h2>
                    </div>

                    <div v-if="recentActivity.length" class="space-y-2">
                        <div
                            v-for="item in recentActivity"
                            :key="item.id"
                            class="flex items-start gap-3 px-3 py-2 rounded-subtle hover:bg-cafe-200/40 dark:hover:bg-surface-dark-2 transition-colors duration-150"
                        >
                            <span
                                class="mt-0.5 w-2 h-2 rounded-full shrink-0"
                                :class="item.status === 'resolved' ? 'bg-state-success' : 'bg-state-warning'"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-cafe-700 dark:text-cafe-200 truncate">{{ item.content }}</p>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                    {{ item.student_name }} · {{ item.group_name }} · {{ item.created_at }}
                                </p>
                            </div>
                            <span class="text-xs font-medium shrink-0" :class="observationTypeColor[item.type] || 'text-cafe-500'">
                                {{ t('observations.type_' + item.type) }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-cafe-400 dark:text-cafe-500 text-center py-6">
                        {{ t('workspace.no_activity') }}
                    </p>
                </section>

                <!-- ═══ 5. INSIGHTS ═══ -->
                <section class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <CpIcon name="alert-triangle" :size="18" class-name="text-cafe-500 dark:text-cafe-400" />
                        <h2 class="text-sm font-semibold text-cafe-700 dark:text-cafe-200">
                            {{ t('workspace.insights') }}
                        </h2>
                    </div>

                    <div v-if="insights.length" class="space-y-2">
                        <a
                            v-for="(insight, idx) in insights"
                            :key="idx"
                            :href="insight.route"
                            class="block border-l-3 rounded-r-subtle px-3 py-2.5 transition-colors duration-150 hover:opacity-80"
                            :class="insightSeverityStyle[insight.severity] || ''"
                        >
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="insightSeverityDot[insight.severity]" />
                                <span class="text-xs font-medium uppercase tracking-wide text-cafe-500 dark:text-cafe-400">
                                    {{ t('insights.severity_' + insight.severity) }}
                                </span>
                            </div>
                            <p class="text-sm text-cafe-700 dark:text-cafe-200 leading-snug">{{ insight.message }}</p>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-1">{{ insight.suggested_action }}</p>
                        </a>
                    </div>
                    <p v-else class="text-sm text-cafe-400 dark:text-cafe-500 text-center py-6">
                        {{ t('workspace.no_insights') }}
                    </p>
                </section>

                <!-- ═══ 6. SUGGESTED ACTIONS ═══ -->
                <section v-if="suggestedActions.length" class="lg:col-span-2 rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <CpIcon name="zap" :size="18" class-name="text-cafe-500 dark:text-cafe-400" />
                        <h2 class="text-sm font-semibold text-cafe-700 dark:text-cafe-200">
                            {{ t('workspace.suggested_actions') }}
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <a
                            v-for="(action, idx) in suggestedActions.slice(0, 5)"
                            :key="idx"
                            :href="action.route ? route(action.route) : '#'"
                            class="flex items-start gap-3 border-l-3 rounded-r-subtle px-4 py-3 bg-cafe-50 dark:bg-surface-dark-2 hover:bg-cafe-200/40 dark:hover:bg-surface-dark-3 transition-colors duration-150"
                            :class="actionSeverityStyle[action.severity] || ''"
                        >
                            <CpIcon :name="action.icon || 'zap'" :size="18" class-name="text-cafe-500 dark:text-cafe-400 mt-0.5 shrink-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-cafe-800 dark:text-cafe-100 leading-snug">{{ action.title }}</p>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5 line-clamp-2">{{ action.description }}</p>
                            </div>
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
