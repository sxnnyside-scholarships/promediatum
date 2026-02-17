<script setup>
/**
 * CpFab — Advanced Intelligent Floating Action Button
 *
 * Phase 2C overhaul:
 * - Actions resolved server-side by FabActionResolver (shared via Inertia props)
 * - Fallback to client-side route detection if server data unavailable
 * - Dynamic visual states: animated gradient border when visual effects ON
 * - Static accent border when effects OFF
 * - Respects prefers-reduced-motion
 * - Max 3 contextual actions, priority-ordered
 * - Badge support for state-aware alerts (pending observations)
 */
import CpIcon from '@/Components/CpIcon.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const { t } = useTranslations();
const page = usePage();
const open = ref(false);

function toggle() {
    open.value = !open.value;
}

function close() {
    open.value = false;
}

/**
 * Visual effects preference from user settings.
 */
const visualEffectsEnabled = computed(() => {
    const settings = page.props.auth.user?.settings ?? {};
    return settings.visual_effects_enabled !== false;
});

/**
 * Server-resolved FAB actions (from FabActionResolver via Inertia shared props).
 * Falls back to client-side route detection if unavailable.
 */
const actions = computed(() => {
    const serverActions = page.props.fab;

    if (Array.isArray(serverActions) && serverActions.length > 0) {
        return serverActions.map(action => ({
            label: t(action.label),
            icon: action.icon,
            href: resolveActionRoute(action),
            badge: action.badge || null,
        }));
    }

    // Fallback: client-side route detection
    return fallbackActions.value;
});

/**
 * Resolve a named route from the server action data.
 */
function resolveActionRoute(action) {
    try {
        if (action.params && Object.keys(action.params).length > 0) {
            // Extract the first param value for simple route resolution
            const paramValues = Object.values(action.params);
            return route(action.route, ...paramValues);
        }
        return route(action.route);
    } catch {
        return '#';
    }
}

/**
 * Fallback client-side actions (legacy behavior, simplified).
 */
const fallbackActions = computed(() => {
    const current = route().current;

    if (current('workspace')) {
        return [
            { label: t('fab.new_group'), href: route('groups.create'), icon: 'users' },
            { label: t('fab.new_student'), href: route('students.create'), icon: 'user' },
            { label: t('fab.new_period'), href: route('periods.create'), icon: 'calendar' },
        ];
    }

    if (current('groups.index')) {
        return [
            { label: t('fab.new_group'), href: route('groups.create'), icon: 'users' },
        ];
    }

    if (current('groups.show')) {
        const groupSlug = page.props.group?.slug;
        if (groupSlug) {
            return [
                { label: t('fab.take_attendance'), href: route('attendance.index', groupSlug), icon: 'clipboard' },
            ];
        }
    }

    if (current('students.index')) {
        return [
            { label: t('fab.new_student'), href: route('students.create'), icon: 'user' },
        ];
    }

    if (current('periods.index') || current('periods.*')) {
        return [
            { label: t('fab.new_period'), href: route('periods.create'), icon: 'calendar' },
        ];
    }

    if (current('exports.*')) {
        return [
            { label: t('fab.new_export'), href: route('exports.history'), icon: 'download' },
        ];
    }

    return [
        { label: t('fab.new_group'), href: route('groups.create'), icon: 'users' },
        { label: t('fab.new_student'), href: route('students.create'), icon: 'user' },
    ];
});
</script>

<template>
    <div class="fixed bottom-6 right-6 z-40 flex flex-col items-end gap-2">
        <!-- Action items (expand upward) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-3 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-2 scale-95"
        >
            <div v-if="open" class="flex flex-col items-end gap-2 mb-2">
                <Link
                    v-for="(action, idx) in actions"
                    :key="idx"
                    :href="action.href"
                    @click="close"
                    class="flex items-center gap-2 px-4 py-2 rounded-full bg-white dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 shadow-lg text-sm font-medium text-cafe-700 dark:text-cafe-200 hover:bg-cafe-100 dark:hover:bg-surface-dark-3 hover:shadow-xl transition-all duration-200"
                    :style="{ transitionDelay: `${idx * 40}ms` }"
                >
                    <CpIcon :name="action.icon" :size="16" />
                    <span>{{ action.label }}</span>
                    <!-- Badge for state alerts (e.g. pending observations count) -->
                    <span
                        v-if="action.badge"
                        class="ml-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] px-1 rounded-full bg-state-danger text-white text-xs font-bold"
                    >
                        {{ action.badge }}
                    </span>
                </Link>
            </div>
        </Transition>

        <!-- FAB trigger button -->
        <div class="relative">
            <!-- Animated gradient border — ONLY when visual effects are ON -->
            <div
                v-if="visualEffectsEnabled"
                class="absolute -inset-[3px] rounded-full fab-gradient-ring motion-reduce:hidden"
                :class="{ 'fab-gradient-spinning': !open }"
            />
            <button
                type="button"
                @click="toggle"
                class="relative flex items-center justify-center w-14 h-14 rounded-full transition-all duration-300"
                :class="[
                    open
                        ? 'rotate-45 shadow-lg'
                        : visualEffectsEnabled
                            ? 'shadow-xl hover:shadow-2xl hover:scale-110'
                            : 'shadow-md hover:shadow-lg hover:scale-105',
                    visualEffectsEnabled
                        ? 'bg-accent-400 text-white hover:bg-accent-500 active:bg-accent-600'
                        : 'bg-cafe-200 dark:bg-surface-dark-2 text-cafe-600 dark:text-cafe-300 ring-1 ring-cafe-300 dark:ring-cafe-600 hover:bg-cafe-300 dark:hover:bg-surface-dark-3'
                ]"
            >
                <CpIcon name="plus" :size="24" :stroke-width="2.5" />
            </button>
        </div>
    </div>

    <!-- Backdrop (click to close) -->
    <Transition
        enter-active-class="transition duration-150"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-30"
            @click="close"
        />
    </Transition>
</template>

<style scoped>
/* ── Animated gradient ring for the FAB (visual effects ON) ── */
.fab-gradient-ring {
    background: conic-gradient(
        from 0deg,
        #f59e0b,
        #ef4444,
        #ec4899,
        #8b5cf6,
        #3b82f6,
        #22c55e,
        #f59e0b
    );
    border-radius: 9999px;
    opacity: 0;
    filter: blur(4px);
    transition: opacity 300ms ease, filter 300ms ease;
    pointer-events: none;
}

.fab-gradient-spinning {
    opacity: 0.8;
    filter: blur(6px);
    animation: fab-glow-spin 2.5s linear infinite;
}

.fab-gradient-spinning:hover {
    opacity: 1;
    filter: blur(8px);
}

@keyframes fab-glow-spin {
    to {
        transform: rotate(360deg);
    }
}

/* Respect reduced motion preference */
@media (prefers-reduced-motion: reduce) {
    .fab-gradient-spinning {
        animation: none;
        opacity: 0.4;
        filter: blur(3px);
    }
}
</style>
