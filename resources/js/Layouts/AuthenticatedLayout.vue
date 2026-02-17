<script setup>
/**
 * AuthenticatedLayout — Café Pedagógico AppShell
 *
 * Structure: Top bar + NavigationColumn sidebar + main content + footer.
 * Top bar: logo, locale switch, theme toggle, user dropdown.
 * Sidebar: Dashboard, Periodos, Grupos*, Estudiantes*, Observaciones*, Ajustes.
 * (* = placeholder for future modules)
 */
import PromediatumLogo from '@/Components/PromediatumLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import LocaleSwitch from '@/Components/LocaleSwitch.vue';
import CpIcon from '@/Components/CpIcon.vue';
import CpFab from '@/Components/CpFab.vue';
import CpToast from '@/Components/CpToast.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { router, usePage, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const { t } = useTranslations();
const page = usePage();

const dropdownOpen = ref(false);
const dropdownRef = ref(null);
const sidebarOpen = ref(true);

// UI preferences from DB
const userSettings = computed(() => page.props.auth.user?.settings ?? {});
const sidebarTrailing = computed(() => userSettings.value.sidebar_position === 'trailing');
const iconsHidden = computed(() => userSettings.value.icons_enabled === false);
const textWeightClass = computed(() => {
    const w = userSettings.value.text_weight;
    if (w === '300') return 'font-light';
    if (w === '500') return 'font-medium';
    if (w === '600') return 'font-semibold';
    return ''; // 400 = normal (default)
});

function toggleDropdown() {
    dropdownOpen.value = !dropdownOpen.value;
}

function closeDropdown(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        dropdownOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', closeDropdown));
onUnmounted(() => document.removeEventListener('click', closeDropdown));

function lock() {
    dropdownOpen.value = false;
    router.post(route('session.lock'));
}

function logout() {
    dropdownOpen.value = false;
    router.post(route('logout'));
}

const navItems = computed(() => [
    { label: t('nav.dashboard'), href: route('workspace'), active: route().current('workspace'), icon: 'home' },
    { label: t('nav.periods'), href: route('periods.index'), active: route().current('periods.*'), icon: 'calendar' },
    { label: t('nav.groups'), href: route('groups.index'), active: route().current('groups.*') || route().current('attendance.*') || route().current('categories.*') || route().current('grades.*'), icon: 'users' },
    { label: t('nav.students'), href: route('students.index'), active: route().current('students.*'), icon: 'user' },
    { label: t('nav.observations'), href: route('observations.index'), active: route().current('observations.*'), icon: 'clipboard' },
    { label: t('nav.exports'), href: route('exports.history'), active: route().current('exports.*'), icon: 'download' },
    { label: t('nav.settings'), href: route('settings.index'), active: route().current('settings.*'), icon: 'settings' },
]);
</script>

<template>
    <div class="min-h-screen bg-cafe-50 dark:bg-surface-dark flex flex-col" :class="[textWeightClass, { 'hide-decorative-icons': iconsHidden }]">
        <!-- Top bar -->
        <header class="border-b border-cafe-200 dark:border-cafe-800 bg-cafe-100 dark:bg-surface-dark-1 z-30">
            <div class="flex items-center justify-between px-6 py-3">
                <div class="flex items-center gap-3">
                    <!-- Sidebar toggle -->
                    <button
                        type="button"
                        @click="sidebarOpen = !sidebarOpen"
                        class="p-1 text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 transition-colors duration-150 lg:hidden"
                    >
                        <CpIcon name="menu" :size="20" />
                    </button>
                    <Link :href="route('workspace')" class="flex items-center gap-3">
                        <PromediatumLogo class="h-7 w-7 text-cafe-600 dark:text-cafe-300" />
                        <span class="text-base font-semibold tracking-tight text-cafe-800 dark:text-cafe-100 font-serif">
                            Promediatum
                        </span>
                    </Link>
                </div>

                <div class="flex items-center gap-4">
                    <LocaleSwitch />
                    <ThemeToggle />

                    <!-- User dropdown -->
                    <div class="relative ml-2" ref="dropdownRef">
                        <button
                            type="button"
                            @click="toggleDropdown"
                            class="flex items-center gap-1.5 text-sm text-cafe-600 dark:text-cafe-300 hover:text-cafe-800 dark:hover:text-cafe-100 transition-colors duration-150"
                        >
                            <span>{{ page.props.auth.user?.full_name }}</span>
                            <CpIcon name="chevron-down" :size="16" class-name="transition-transform duration-150" :class="{ 'rotate-180': dropdownOpen }" />
                        </button>

                        <Transition
                            enter-active-class="transition duration-100 ease-out"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div
                                v-if="dropdownOpen"
                                class="absolute right-0 mt-2 w-48 bg-white dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-subtle shadow-lg py-1 z-50"
                            >
                                <Link
                                    :href="route('profile.index')"
                                    class="block px-4 py-2 text-sm text-cafe-700 dark:text-cafe-200 hover:bg-cafe-100 dark:hover:bg-surface-dark-3 transition-colors duration-150"
                                    @click="dropdownOpen = false"
                                >
                                    {{ t('auth.profile') }}
                                </Link>

                                <div class="cp-separator my-1" />

                                <button
                                    type="button"
                                    @click="lock"
                                    class="w-full text-left px-4 py-2 text-sm text-cafe-700 dark:text-cafe-200 hover:bg-cafe-100 dark:hover:bg-surface-dark-3 transition-colors duration-150"
                                >
                                    {{ t('auth.lock') }}
                                </button>

                                <button
                                    type="button"
                                    @click="logout"
                                    class="w-full text-left px-4 py-2 text-sm text-state-danger hover:bg-cafe-100 dark:hover:bg-surface-dark-3 transition-colors duration-150"
                                >
                                    {{ t('auth.logout') }}
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex flex-1" :class="{ 'flex-row-reverse': sidebarTrailing }">
            <!-- NavigationColumn sidebar -->
            <aside
                class="w-56 shrink-0 bg-cafe-100/50 dark:bg-surface-dark-1/50 transition-all duration-200"
                :class="[
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full absolute lg:relative lg:translate-x-0',
                    sidebarTrailing
                        ? 'border-l border-cafe-200 dark:border-cafe-800'
                        : 'border-r border-cafe-200 dark:border-cafe-800',
                ]"
            >
                <nav class="py-4 px-3 space-y-1">
                    <template v-for="item in navItems" :key="item.label">
                        <Link
                            v-if="!item.disabled"
                            :href="item.href"
                            class="flex items-center gap-2.5 px-3 py-2 rounded-subtle text-sm transition-colors duration-150"
                            :class="item.active
                                ? 'bg-accent-100 dark:bg-accent-900/30 text-accent-700 dark:text-accent-300 font-medium'
                                : 'text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200/60 dark:hover:bg-surface-dark-2 hover:text-cafe-800 dark:hover:text-cafe-100'"
                        >
                            <CpIcon
                                :name="item.icon"
                                :size="16"
                                :stroke-width="item.active ? 2 : 1.5"
                                class-name="shrink-0 decorative-icon"
                            />
                            <span>{{ item.label }}</span>
                        </Link>
                        <span
                            v-else
                            class="flex items-center gap-2.5 px-3 py-2 rounded-subtle text-sm text-cafe-400 dark:text-cafe-600 cursor-default"
                        >
                            <CpIcon :name="item.icon" :size="16" :stroke-width="1.5" class-name="shrink-0 decorative-icon" />
                            <span>{{ item.label }}</span>
                        </span>
                    </template>
                </nav>
            </aside>

            <!-- Main content -->
            <main class="flex-1 px-6 py-8 min-w-0">
                <slot />
            </main>
        </div>

        <!-- Footer -->
        <footer class="px-6 py-4 text-center border-t border-cafe-200 dark:border-cafe-800">
            <p class="text-xs text-cafe-400 dark:text-cafe-500">
                {{ t('brand.credit') }}
                <span class="mx-1">·</span>
                <a
                    href="https://www.sxnnysideproject.com"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="underline underline-offset-2 hover:text-cafe-600 dark:hover:text-cafe-300 transition-colors duration-150"
                >
                    {{ t('brand.project') }}
                </a>
            </p>
        </footer>

        <!-- Intelligent FAB -->
        <CpFab v-if="userSettings.fab_enabled !== false" />

        <!-- Toast notifications -->
        <CpToast />
    </div>
</template>
