<script setup>
/**
 * CpFab — Intelligent Floating Action Button
 *
 * Context-aware: shows different actions based on current route.
 * Can be disabled via user settings (fab_enabled).
 * Renders as a fixed-position overlay in the bottom-right.
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
 * Context-aware actions based on current route.
 */
const actions = computed(() => {
    const current = route().current;

    // Workspace
    if (current('workspace')) {
        return [
            { label: t('fab.new_group'), href: route('groups.create'), icon: 'users' },
            { label: t('fab.new_student'), href: route('students.create'), icon: 'user' },
            { label: t('fab.new_period'), href: route('periods.create'), icon: 'calendar' },
        ];
    }

    // Groups index
    if (current('groups.index')) {
        return [
            { label: t('fab.new_group'), href: route('groups.create'), icon: 'users' },
        ];
    }

    // Group show — contextual actions for that group
    if (current('groups.show')) {
        const groupSlug = page.props.group?.slug;
        if (groupSlug) {
            return [
                { label: t('fab.take_attendance'), href: route('attendance.index', groupSlug), icon: 'clipboard' },
            ];
        }
    }

    // Students index
    if (current('students.index')) {
        return [
            { label: t('fab.new_student'), href: route('students.create'), icon: 'user' },
        ];
    }

    // Periods index
    if (current('periods.index') || current('periods.*')) {
        return [
            { label: t('fab.new_period'), href: route('periods.create'), icon: 'calendar' },
        ];
    }

    // Default — most common actions
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
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div v-if="open" class="flex flex-col items-end gap-2 mb-2">
                <Link
                    v-for="(action, idx) in actions"
                    :key="idx"
                    :href="action.href"
                    @click="close"
                    class="flex items-center gap-2 px-4 py-2 rounded-full bg-white dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 shadow-lg text-sm font-medium text-cafe-700 dark:text-cafe-200 hover:bg-cafe-100 dark:hover:bg-surface-dark-3 transition-colors duration-150"
                >
                    <CpIcon :name="action.icon" :size="16" />
                    {{ action.label }}
                </Link>
            </div>
        </Transition>

        <!-- FAB trigger button -->
        <button
            type="button"
            @click="toggle"
            class="flex items-center justify-center w-12 h-12 rounded-full bg-accent-400 text-white shadow-lg hover:bg-accent-500 active:bg-accent-600 transition-all duration-150"
            :class="{ 'rotate-45': open }"
        >
            <CpIcon name="plus" :size="24" :stroke-width="2" />
        </button>
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
