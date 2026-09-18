<script setup>
/**
 * AuthenticatedLayout — Educator Digital Workspace AppShell
 *
 * Structure: Fixed top bar + full-height sidebar + scrollable main viewport + pinned footer.
 * The footer is permanently fixed at the bottom of the content column and never collides with the sidebar.
 */

import { Link, router, usePage } from '@inertiajs/vue3';
import {
    CalendarMonthRegular,
    ClipboardRegular,
    DownloadRegular,
    DownSmallRegular,
    ExternalLinkRegular,
    GroupRegular,
    Home4Regular,
    MenuRegular,
    Settings3Regular,
    User4Regular,
} from '@mingcute/vue/core-regular';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import CpFab from '@/Components/CpFab.vue';
import CpToast from '@/Components/CpToast.vue';
import LocaleSwitch from '@/Components/LocaleSwitch.vue';
import PromediatumLogo from '@/Components/PromediatumLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useTranslations } from '@/composables/useTranslations';

const { t, locale } = useTranslations();
const page = usePage();

const dropdownOpen = ref(false);
const dropdownRef = ref(null);
const sidebarOpen = ref(true);

const sxnnysideUrl = computed(() => {
    const loc = locale.value === 'en' ? 'en' : 'es';
    return `https://sxnnysideproject.com/${loc}/realms/sxnnyside-scholarships/`;
});

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
    {
        label: t('nav.dashboard'),
        href: route('workspace'),
        active: route().current('workspace'),
        icon: Home4Regular,
    },
    {
        label: t('nav.periods'),
        href: route('periods.index'),
        active: route().current('periods.*'),
        icon: CalendarMonthRegular,
    },
    {
        label: t('nav.groups'),
        href: route('groups.index'),
        active:
            route().current('groups.*') ||
            route().current('attendance.*') ||
            route().current('categories.*') ||
            route().current('grades.*'),
        icon: GroupRegular,
    },
    {
        label: t('nav.students'),
        href: route('students.index'),
        active: route().current('students.*'),
        icon: User4Regular,
    },
    {
        label: t('nav.observations'),
        href: route('observations.index'),
        active: route().current('observations.*'),
        icon: ClipboardRegular,
    },
    {
        label: t('nav.exports'),
        href: route('exports.history'),
        active: route().current('exports.*'),
        icon: DownloadRegular,
    },
    {
        label: t('nav.settings'),
        href: route('settings.index'),
        active: route().current('settings.*'),
        icon: Settings3Regular,
    },
]);
</script>

<template>
    <div class="h-screen w-full flex flex-col overflow-hidden bg-cafe-50 dark:bg-surface-dark text-cafe-800 dark:text-cafe-100 selection:bg-accent-400 selection:text-white" :class="[textWeightClass, { 'hide-decorative-icons': iconsHidden }]">
        <!-- Top bar -->
        <header class="shrink-0 border-b border-cafe-200 dark:border-cafe-800 bg-cafe-100 dark:bg-surface-dark-1 z-30">
            <div class="flex items-center justify-between px-6 py-3">
                <div class="flex items-center gap-3">
                    <!-- Sidebar toggle for mobile/responsive -->
                    <button
                        type="button"
                        @click="sidebarOpen = !sidebarOpen"
                        class="p-1 text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 transition-colors duration-150 lg:hidden"
                        aria-label="Toggle Navigation"
                    >
                        <MenuRegular class="w-5 h-5" />
                    </button>
                    <Link :href="route('workspace')" class="flex items-center gap-2.5">
                        <PromediatumLogo class="h-7 w-7 text-cafe-600 dark:text-cafe-300 drop-shadow-sm" />
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
                            <DownSmallRegular class="w-4 h-4 transition-transform duration-150" :class="{ 'rotate-180': dropdownOpen }" />
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
                                class="absolute right-0 mt-2 w-48 bg-white dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-xl shadow-lg py-1 z-50"
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

        <!-- Body viewport: Sidebar + Main Content Column -->
        <div class="flex flex-1 min-h-0 overflow-hidden" :class="{ 'flex-row-reverse': sidebarTrailing }">
            <!-- NavigationColumn sidebar: full-height, cleanly isolated from footer -->
            <aside
                class="w-56 shrink-0 h-full overflow-y-auto bg-cafe-100/60 dark:bg-surface-dark-1/60 transition-all duration-200 z-20 flex flex-col justify-between"
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
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors duration-150"
                            :class="item.active
                                ? 'bg-accent-100 dark:bg-accent-900/30 text-accent-700 dark:text-accent-300 font-medium'
                                : 'text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200/60 dark:hover:bg-surface-dark-2 hover:text-cafe-800 dark:hover:text-cafe-100'"
                        >
                            <component
                                :is="item.icon"
                                class="w-4 h-4 shrink-0 decorative-icon"
                                :class="{ 'stroke-2': item.active }"
                            />
                            <span>{{ item.label }}</span>
                        </Link>
                        <span
                            v-else
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-cafe-400 dark:text-cafe-600 cursor-default"
                        >
                            <component :is="item.icon" class="w-4 h-4 shrink-0 decorative-icon" />
                            <span>{{ item.label }}</span>
                        </span>
                    </template>
                </nav>
            </aside>

            <!-- Main viewport container: holds scrollable view and pinned footer -->
            <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden relative">
                <!-- Main scrollable content -->
                <main class="flex-1 overflow-y-auto px-6 py-8 min-w-0">
                    <slot />
                </main>

                <!-- Fixed footer pinned at bottom of content viewport (never collides with sidebar) -->
                <footer class="shrink-0 px-6 py-2.5 border-t border-cafe-200/80 dark:border-cafe-800/80 bg-cafe-100/90 dark:bg-surface-dark-1/90 backdrop-blur-sm text-center z-10">
                    <a
                        :href="sxnnysideUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-1.5 text-[11px] font-medium text-cafe-500 hover:text-accent-600 dark:text-cafe-400 dark:hover:text-accent-300 transition-colors group"
                    >
                        <span>{{ t('brand.credit') }}</span>
                        <ExternalLinkRegular class="w-3.5 h-3.5 opacity-70 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" />
                    </a>
                </footer>
            </div>
        </div>

        <!-- Intelligent FAB -->
        <CpFab v-if="userSettings.fab_enabled !== false" />

        <!-- Toast notifications -->
        <CpToast />
    </div>
</template>
