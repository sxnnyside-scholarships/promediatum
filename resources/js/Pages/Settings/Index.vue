<script setup>
/**
 * Settings Index — /settings
 * Two-column grid: Left (60%) Appearance + Language + Integrations, Right (40%) Interface.
 * Interface prefs persist to DB via PUT /settings.
 * SMTP settings persist via PUT /settings/smtp.
 * Component variety: segmented controls, toggle switches, radio groups.
 */

import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpIcon from '@/Components/CpIcon.vue';
import CpInput from '@/Components/CpInput.vue';
import { useTheme } from '@/composables/useTheme';
import { useToast } from '@/composables/useToast';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const { t, locale } = useTranslations();
const { mode, setMode } = useTheme();
const toast = useToast();
const page = usePage();

function switchLocale(newLocale) {
    router.post(
        route('locale.update'),
        {
            locale: newLocale,
        },
        {
            preserveState: false,
            preserveScroll: true,
        },
    );
}

// Interface preferences — loaded from DB via shared props
const userSettings = computed(() => page.props.auth.user?.settings ?? {});
const sidebarPosition = ref(userSettings.value.sidebar_position || 'leading');
const textWeight = ref(userSettings.value.text_weight || '400');
const fabEnabled = ref(userSettings.value.fab_enabled !== false);
const visualEffectsEnabled = ref(userSettings.value.visual_effects_enabled !== false);
const iconsEnabled = ref(userSettings.value.icons_enabled !== false);

function persistSetting(key, value) {
    router.put(
        route('settings.update'),
        {
            [key]: value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
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
function toggleVisualEffects() {
    visualEffectsEnabled.value = !visualEffectsEnabled.value;
    persistSetting('visual_effects_enabled', visualEffectsEnabled.value);
}
function toggleIcons() {
    iconsEnabled.value = !iconsEnabled.value;
    persistSetting('icons_enabled', iconsEnabled.value);
}

// ── SMTP Settings ──

const smtpLoading = ref(true);
const smtpSaving = ref(false);
const smtpTesting = ref(false);
const smtpConfigured = ref(false);
const smtpVerified = ref(false);

const smtpForm = ref({
    host: '',
    port: 587,
    username: '',
    password: '',
    encryption: 'tls',
    from_name: '',
    from_email: '',
});

const smtpExpanded = ref(false);

onMounted(async () => {
    try {
        const res = await axios.get(route('settings.smtp.show'));
        const data = res.data;
        smtpConfigured.value = data.configured;
        smtpVerified.value = data.verified ?? false;
        if (data.settings) {
            smtpForm.value = {
                host: data.settings.host || '',
                port: data.settings.port || 587,
                username: data.settings.username || '',
                password: '', // never returned from server
                encryption: data.settings.encryption || 'tls',
                from_name: data.settings.from_name || '',
                from_email: data.settings.from_email || '',
            };
        }
    } catch {
        // SMTP not configured — that's fine
    } finally {
        smtpLoading.value = false;
    }
});

function saveSmtp() {
    smtpSaving.value = true;
    router.put(route('settings.smtp.update'), smtpForm.value, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            smtpConfigured.value = true;
            smtpVerified.value = false; // reset until tested
            toast.success(t('settings.smtp_saved'));
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError || t('settings.smtp_save_error'));
        },
        onFinish: () => {
            smtpSaving.value = false;
        },
    });
}

async function testSmtp() {
    smtpTesting.value = true;
    try {
        const res = await axios.post(route('settings.smtp.test'));
        if (res.data.success) {
            smtpVerified.value = true;
            toast.success(t('settings.smtp_test_success'));
        } else {
            toast.error(res.data.message || t('settings.smtp_test_error'));
        }
    } catch (err) {
        const msg = err.response?.data?.message || t('settings.smtp_test_error');
        toast.error(msg);
    } finally {
        smtpTesting.value = false;
    }
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

                    <!-- ── Integrations — Email (SMTP) ── -->
                    <section class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                        <div class="flex items-start gap-3 mb-4">
                            <CpIcon name="mail" :size="20" class-name="mt-0.5 shrink-0 text-cafe-500 dark:text-cafe-400" />
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-base font-semibold text-cafe-800 dark:text-cafe-100">
                                        {{ t('settings.smtp_title') }}
                                    </h2>
                                    <span v-if="smtpVerified" class="flex items-center gap-1 text-xs text-state-success">
                                        <CpIcon name="check-circle" :size="14" />
                                        {{ t('settings.smtp_verified') }}
                                    </span>
                                    <span v-else-if="smtpConfigured" class="text-xs text-state-warning">
                                        {{ t('settings.smtp_not_verified') }}
                                    </span>
                                </div>
                                <p class="text-sm text-cafe-500 dark:text-cafe-400 mt-0.5">
                                    {{ t('settings.smtp_description') }}
                                </p>
                            </div>
                        </div>

                        <!-- Expand/Collapse toggle -->
                        <button
                            type="button"
                            @click="smtpExpanded = !smtpExpanded"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-subtle text-sm text-cafe-600 dark:text-cafe-300 hover:bg-cafe-200/40 dark:hover:bg-surface-dark-2 transition-colors duration-150"
                        >
                            <span>{{ smtpExpanded ? t('settings.smtp_collapse') : t('settings.smtp_expand') }}</span>
                            <CpIcon :name="smtpExpanded ? 'chevron-down' : 'chevron-right'" :size="16" />
                        </button>

                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0"
                            enter-to-class="opacity-100"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <div v-if="smtpExpanded" class="mt-4 space-y-4">
                                <div v-if="smtpLoading" class="text-sm text-cafe-500 dark:text-cafe-400 py-4 text-center">
                                    {{ t('settings.smtp_loading') }}
                                </div>
                                <template v-else>
                                    <!-- SMTP Form Fields -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <CpInput
                                            id="smtp_host"
                                            v-model="smtpForm.host"
                                            :label="t('settings.smtp_host')"
                                            :placeholder="t('settings.smtp_host_placeholder')"
                                        />
                                        <CpInput
                                            id="smtp_port"
                                            v-model="smtpForm.port"
                                            type="number"
                                            :label="t('settings.smtp_port')"
                                            placeholder="587"
                                        />
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <CpInput
                                            id="smtp_username"
                                            v-model="smtpForm.username"
                                            :label="t('settings.smtp_username')"
                                            :placeholder="t('settings.smtp_username_placeholder')"
                                        />
                                        <CpInput
                                            id="smtp_password"
                                            v-model="smtpForm.password"
                                            type="password"
                                            :label="t('settings.smtp_password')"
                                            placeholder="••••••••"
                                        />
                                    </div>

                                    <!-- Encryption -->
                                    <div>
                                        <span class="block text-sm font-medium text-cafe-700 dark:text-cafe-200 mb-2">
                                            {{ t('settings.smtp_encryption') }}
                                        </span>
                                        <div class="inline-flex rounded-subtle border border-cafe-200 dark:border-cafe-700 bg-white dark:bg-surface-dark-2 p-0.5">
                                            <button
                                                v-for="opt in ['tls', 'ssl', 'none']"
                                                :key="opt"
                                                type="button"
                                                @click="smtpForm.encryption = opt"
                                                class="px-3 py-1 text-xs font-medium rounded-subtle transition-colors duration-150"
                                                :class="smtpForm.encryption === opt
                                                    ? 'bg-accent-400 text-white dark:bg-accent-500'
                                                    : 'text-cafe-600 dark:text-cafe-300 hover:text-cafe-800 dark:hover:text-cafe-100'"
                                            >
                                                {{ opt.toUpperCase() }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <CpInput
                                            id="smtp_from_name"
                                            v-model="smtpForm.from_name"
                                            :label="t('settings.smtp_from_name')"
                                            :placeholder="t('settings.smtp_from_name_placeholder')"
                                        />
                                        <CpInput
                                            id="smtp_from_email"
                                            v-model="smtpForm.from_email"
                                            type="email"
                                            :label="t('settings.smtp_from_email')"
                                            placeholder="you@example.com"
                                        />
                                    </div>

                                    <!-- Helper Notes -->
                                    <div class="rounded-subtle bg-cafe-200/40 dark:bg-surface-dark-2 p-3 text-xs text-cafe-500 dark:text-cafe-400 space-y-1">
                                        <p><strong>Gmail:</strong> {{ t('settings.smtp_gmail_hint') }}</p>
                                        <p><strong>Outlook:</strong> {{ t('settings.smtp_outlook_hint') }}</p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-3">
                                        <CpButton @click="saveSmtp" :disabled="smtpSaving">
                                            {{ smtpSaving ? t('settings.saving') : t('settings.save') }}
                                        </CpButton>
                                        <CpButton
                                            variant="secondary"
                                            @click="testSmtp"
                                            :disabled="smtpTesting || !smtpConfigured"
                                        >
                                            {{ smtpTesting ? t('settings.smtp_testing') : t('settings.smtp_test') }}
                                        </CpButton>
                                    </div>
                                </template>
                            </div>
                        </Transition>
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

                        <div class="border-t border-cafe-200 dark:border-cafe-700" />

                        <!-- Visual Effects — Toggle -->
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="block text-sm font-medium text-cafe-700 dark:text-cafe-200">
                                    {{ t('settings.visual_effects') }}
                                </span>
                                <span class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5 block">
                                    {{ t('settings.visual_effects_description') }}
                                </span>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="visualEffectsEnabled"
                                @click="toggleVisualEffects"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none mt-0.5"
                                :class="visualEffectsEnabled ? 'bg-accent-500' : 'bg-cafe-300 dark:bg-surface-dark-3'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 ease-in-out"
                                    :class="visualEffectsEnabled ? 'translate-x-5' : 'translate-x-0'"
                                />
                            </button>
                        </div>

                        <div class="border-t border-cafe-200 dark:border-cafe-700" />

                        <!-- Decorative Icons — Toggle -->
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="block text-sm font-medium text-cafe-700 dark:text-cafe-200">
                                    {{ t('settings.icons_enabled') }}
                                </span>
                                <span class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5 block">
                                    {{ t('settings.icons_enabled_description') }}
                                </span>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="iconsEnabled"
                                @click="toggleIcons"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none mt-0.5"
                                :class="iconsEnabled ? 'bg-accent-500' : 'bg-cafe-300 dark:bg-surface-dark-3'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 ease-in-out"
                                    :class="iconsEnabled ? 'translate-x-5' : 'translate-x-0'"
                                />
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
