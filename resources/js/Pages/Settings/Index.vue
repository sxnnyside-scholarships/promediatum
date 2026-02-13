<script setup>
/**
 * Settings Index — /settings
 * Two-column grid: Left (60%) Appearance + Language, Right (40%) Interface.
 * Interface prefs persist to DB via PUT /settings.
 * Component variety: segmented controls, toggle switches, radio groups.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpIcon from '@/Components/CpIcon.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { useTheme } from '@/composables/useTheme.js';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const { t, locale } = useTranslations();
const { mode, setMode } = useTheme();
const page = usePage();

function switchLocale(newLocale) {
    router.post(route('locale.update'), {
        locale: newLocale,
    }, {
        preserveState: false,
        preserveScroll: true,
    });
}

// Interface preferences — loaded from DB via shared props
const userSettings = computed(() => page.props.auth.user?.settings ?? {});
const sidebarPosition = ref(userSettings.value.sidebar_position || 'leading');
const textWeight = ref(userSettings.value.text_weight || '400');
const fabEnabled = ref(userSettings.value.fab_enabled !== false);

function persistSetting(key, value) {
    router.put(route('settings.update'), {
        [key]: value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function setSidebarPosition(pos) {
    sidebarPosition.value = pos;
    persistSetting('sidebar_position', pos);
}
function setTextWeight(w) {
    textWeight.value = w;
    persistSetting('text_weight', w);
}
function toggleFab() {
    fabEnabled.value = !fabEnabled.value;
    persistSetting('fab_enabled', fabEnabled.value);
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('settings.title')" />

        <div>
            <h1 class="font-serif mb-8">{{ t('settings.title') }}</h1>

            <!-- Two-column grid: 60 / 40 -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

                <!-- ═══ LEFT COLUMN (3/5 = 60%) ═══ -->
                <div class="lg:col-span-3 space-y-8">

                    <!-- ── Appearance ── -->
                    <section class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                        <div class="flex items-start gap-3 mb-4">
                            <CpIcon name="sun" :size="20" class-name="mt-0.5 shrink-0 text-cafe-500 dark:text-cafe-400" />
                            <div>
                                <h2 class="text-base font-semibold text-cafe-800 dark:text-cafe-100">
                                    {{ t('settings.appearance') }}
                                </h2>
                                <p class="text-sm text-cafe-500 dark:text-cafe-400 mt-0.5">
                                    {{ t('settings.theme_description') }}
                                </p>
                            </div>
                        </div>

                        <!-- Segmented control: System / Light / Dark -->
                        <div class="inline-flex rounded-subtle border border-cafe-200 dark:border-cafe-700 bg-white dark:bg-surface-dark-2 p-0.5">
                            <button
                                v-for="opt in [
                                    { value: 'system', label: t('theme.system') },
                                    { value: 'light', label: t('theme.light') },
                                    { value: 'dark', label: t('theme.dark') },
                                ]"
                                :key="opt.value"
                                type="button"
                                @click="setMode(opt.value)"
                                class="px-4 py-1.5 text-sm font-medium rounded-subtle transition-colors duration-150"
                                :class="mode === opt.value
                                    ? 'bg-accent-400 text-white dark:bg-accent-500'
                                    : 'text-cafe-600 dark:text-cafe-300 hover:text-cafe-800 dark:hover:text-cafe-100'"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </section>

                    <!-- ── Language ── -->
                    <section class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                        <div class="flex items-start gap-3 mb-4">
                            <CpIcon name="globe" :size="20" class-name="mt-0.5 shrink-0 text-cafe-500 dark:text-cafe-400" />
                            <div>
                                <h2 class="text-base font-semibold text-cafe-800 dark:text-cafe-100">
                                    {{ t('settings.language') }}
                                </h2>
                                <p class="text-sm text-cafe-500 dark:text-cafe-400 mt-0.5">
                                    {{ t('settings.language_description') }}
                                </p>
                            </div>
                        </div>

                        <!-- Segmented control: Español / English -->
                        <div class="inline-flex rounded-subtle border border-cafe-200 dark:border-cafe-700 bg-white dark:bg-surface-dark-2 p-0.5">
                            <button
                                v-for="opt in [
                                    { value: 'es', label: 'Español' },
                                    { value: 'en', label: 'English' },
                                ]"
                                :key="opt.value"
                                type="button"
                                @click="switchLocale(opt.value)"
                                class="px-4 py-1.5 text-sm font-medium rounded-subtle transition-colors duration-150"
                                :class="locale === opt.value
                                    ? 'bg-accent-400 text-white dark:bg-accent-500'
                                    : 'text-cafe-600 dark:text-cafe-300 hover:text-cafe-800 dark:hover:text-cafe-100'"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </section>
                </div>

                <!-- ═══ RIGHT COLUMN (2/5 = 40%) ═══ -->
                <div class="lg:col-span-2">

                    <!-- ── Interface ── -->
                    <section class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6 space-y-6">
                        <div class="flex items-start gap-3">
                            <CpIcon name="grid" :size="20" class-name="mt-0.5 shrink-0 text-cafe-500 dark:text-cafe-400" />
                            <div>
                                <h2 class="text-base font-semibold text-cafe-800 dark:text-cafe-100">
                                    {{ t('settings.interface') }}
                                </h2>
                                <p class="text-sm text-cafe-500 dark:text-cafe-400 mt-0.5">
                                    {{ t('settings.interface_description') }}
                                </p>
                            </div>
                        </div>

                        <!-- Sidebar Position — Toggle Switch -->
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="block text-sm font-medium text-cafe-700 dark:text-cafe-200">
                                    {{ t('settings.sidebar_position') }}
                                </span>
                                <span class="text-xs text-cafe-500 dark:text-cafe-400">
                                    {{ sidebarPosition === 'leading' ? t('settings.leading') : t('settings.trailing') }}
                                </span>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="sidebarPosition === 'trailing'"
                                @click="setSidebarPosition(sidebarPosition === 'leading' ? 'trailing' : 'leading')"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="sidebarPosition === 'trailing' ? 'bg-accent-500' : 'bg-cafe-300 dark:bg-surface-dark-3'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 ease-in-out"
                                    :class="sidebarPosition === 'trailing' ? 'translate-x-5' : 'translate-x-0'"
                                />
                            </button>
                        </div>

                        <div class="border-t border-cafe-200 dark:border-cafe-700" />

                        <!-- Text Weight — Radio Group -->
                        <div>
                            <span class="block text-sm font-medium text-cafe-700 dark:text-cafe-200 mb-3">
                                {{ t('settings.text_weight') }}
                            </span>
                            <div class="space-y-2">
                                <label
                                    v-for="opt in [
                                        { value: '300', label: t('settings.weight_light'), desc: t('settings.weight_light_desc'), cls: 'font-light' },
                                        { value: '400', label: t('settings.weight_normal'), desc: t('settings.weight_normal_desc'), cls: 'font-normal' },
                                        { value: '500', label: t('settings.weight_medium'), desc: t('settings.weight_medium_desc'), cls: 'font-medium' },
                                        { value: '600', label: t('settings.weight_strong'), desc: t('settings.weight_strong_desc'), cls: 'font-semibold' },
                                    ]"
                                    :key="opt.value"
                                    class="flex items-center gap-3 px-3 py-2 rounded-subtle cursor-pointer transition-colors duration-150"
                                    :class="textWeight === opt.value
                                        ? 'bg-accent-100/60 dark:bg-accent-900/20'
                                        : 'hover:bg-cafe-200/40 dark:hover:bg-surface-dark-2'"
                                >
                                    <span
                                        class="flex items-center justify-center w-4 h-4 rounded-full border-2 transition-colors duration-150"
                                        :class="textWeight === opt.value
                                            ? 'border-accent-500'
                                            : 'border-cafe-400 dark:border-cafe-600'"
                                    >
                                        <span
                                            v-if="textWeight === opt.value"
                                            class="w-2 h-2 rounded-full bg-accent-500"
                                        />
                                    </span>
                                    <input
                                        type="radio"
                                        name="text_weight"
                                        :value="opt.value"
                                        :checked="textWeight === opt.value"
                                        @change="setTextWeight(opt.value)"
                                        class="sr-only"
                                    />
                                    <div>
                                        <span class="text-sm text-cafe-800 dark:text-cafe-100" :class="opt.cls">
                                            {{ opt.label }}
                                        </span>
                                        <span class="block text-xs text-cafe-400 dark:text-cafe-500">
                                            {{ opt.desc }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="border-t border-cafe-200 dark:border-cafe-700" />

                        <!-- Intelligent FAB — Toggle with Description -->
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="block text-sm font-medium text-cafe-700 dark:text-cafe-200">
                                    {{ t('settings.fab') }}
                                </span>
                                <span class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5 block">
                                    {{ t('settings.fab_description') }}
                                </span>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="fabEnabled"
                                @click="toggleFab"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none mt-0.5"
                                :class="fabEnabled ? 'bg-accent-500' : 'bg-cafe-300 dark:bg-surface-dark-3'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 ease-in-out"
                                    :class="fabEnabled ? 'translate-x-5' : 'translate-x-0'"
                                />
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
