<script setup lang="ts">
/**
 * Settings Index — /settings
 * Unified with the Café Pedagógico Design System:
 * - System Settings Hero & Pedagogical Ergonomics Guidance
 * - Two-column responsive grid:
 *   Left (60%):
 *     1. System Appearance (Light / Dark / System cards with MingCute icons & active rings)
 *     2. Language & Regionalization (Español / English with visual cards)
 *     3. Email Integration (SMTP) with expandable drawer, verification status, and live test
 *   Right (40%):
 *     4. Interface & Visual Ergonomics:
 *        - Sidebar position with visual wireframe mini-cards (Left / Right)
 *        - Typography weight selector with live interactive text sample preview
 *        - Feature toggles (Intelligent FAB, Visual Effects, Decorative Icons)
 */

import { Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertRegular,
    BulbRegular,
    CheckCircleRegular,
    CheckRegular,
    ComputerRegular,
    Delete2Regular,
    DownloadRegular,
    DownSmallRegular,
    EyeRegular,
    Folder2Regular,
    FontRegular,
    Key1Regular,
    Layout2Regular,
    LayoutLeftRegular,
    LayoutRightRegular,
    LockRegular,
    MailRegular,
    MoonRegular,
    PaletteRegular,
    Refresh1Regular,
    RightSmallRegular,
    SendPlaneRegular,
    ServerRegular,
    Settings3Regular,
    ShieldRegular,
    SparklesRegular,
    SunRegular,
    Translate2Regular,
} from '@mingcute/vue/core-regular';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import {
    type AppPaths,
    type BackendHealth,
    type SystemInfo,
    useTauri,
} from '@/composables/useTauri';
import { useTheme } from '@/composables/useTheme';
import { useToast } from '@/composables/useToast';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface BackupItem {
    filename: string;
    path: string;
    size: number;
    created_at?: string;
    date?: string;
}

interface Props {
    settings?: Record<string, any>;
    backups?: BackupItem[];
}

const props = withDefaults(defineProps<Props>(), {
    settings: () => ({}),
    backups: () => [],
});

const { t, locale } = useTranslations();
const { mode, setMode } = useTheme();
const toast = useToast();
const page = usePage();

// ── Desktop & Tauri Native IPC ──
const { isTauri, getSystemInfo, getAppPaths, pingBackend, revealInFileManager } = useTauri();
const tauriSystemInfo = ref<SystemInfo | null>(null);
const tauriAppPaths = ref<AppPaths | null>(null);
const tauriHealth = ref<BackendHealth | null>(null);

onMounted(async () => {
    if (isTauri.value) {
        tauriSystemInfo.value = await getSystemInfo();
        tauriAppPaths.value = await getAppPaths();
        tauriHealth.value = await pingBackend();
    }
});

function handleOpenBackupsFolder() {
    if (tauriAppPaths.value?.backups_dir) {
        revealInFileManager(tauriAppPaths.value.backups_dir);
    }
}

// ── Backup Management ──
const createPassword = ref('');
const restorePassword = ref('');
const restoreTarget = ref<BackupItem | null>(null);
const showRestoreModal = ref(false);
const isCreatingBackup = ref(false);
const isRestoringBackup = ref(false);
const isPruningBackups = ref(false);

function formatBytes(bytes: number): string {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / k ** i).toFixed(1))} ${sizes[i]}`;
}

function handleCreateBackup() {
    isCreatingBackup.value = true;
    router.post(
        route('backup.store'),
        {
            password: createPassword.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                createPassword.value = '';
                toast.success(t('backup.created_successfully'));
            },
            onError: (errors) => {
                const msg =
                    errors.backup ||
                    errors.password ||
                    t('backup.creation_failed', { error: 'Error' });
                toast.error(msg);
            },
            onFinish: () => (isCreatingBackup.value = false),
        },
    );
}

function openRestoreModal(backup: BackupItem) {
    restoreTarget.value = backup;
    restorePassword.value = '';
    showRestoreModal.value = true;
}

function closeRestoreModal() {
    showRestoreModal.value = false;
    restoreTarget.value = null;
    restorePassword.value = '';
}

function handleConfirmRestore() {
    if (!restoreTarget.value) return;
    isRestoringBackup.value = true;
    router.post(
        route('backup.restore'),
        {
            backup_path: restoreTarget.value.path,
            password: restorePassword.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeRestoreModal();
                toast.success(t('backup.restored_successfully'));
            },
            onError: (errors) => {
                const msg =
                    errors.backup ||
                    errors.password ||
                    t('backup.restore_failed', { error: 'Error' });
                toast.error(msg);
            },
            onFinish: () => (isRestoringBackup.value = false),
        },
    );
}

function handleDeleteBackup(backup: BackupItem) {
    if (!confirm(t('backup.confirm_delete'))) return;
    router.delete(route('backup.destroy'), {
        data: { backup_path: backup.path },
        preserveScroll: true,
        onSuccess: () => toast.success(t('backup.deleted_successfully')),
        onError: (errors) => toast.error(errors.backup || t('backup.delete_failed')),
    });
}

function handleDownloadBackup(backup: BackupItem) {
    window.location.href = `${route('backup.download')}?backup_path=${encodeURIComponent(backup.path)}`;
}

function handlePruneBackups() {
    isPruningBackups.value = true;
    router.post(
        route('backup.prune'),
        { keep: 5 },
        {
            preserveScroll: true,
            onSuccess: () => toast.success(t('backup.pruned_successfully', { count: '5' })),
            onError: (errors) => toast.error(errors.backup || 'Error'),
            onFinish: () => (isPruningBackups.value = false),
        },
    );
}

// ── Language Switcher ──
function switchLocale(newLocale: string) {
    if (locale.value === newLocale) return;
    router.post(
        route('locale.update'),
        { locale: newLocale },
        {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    newLocale === 'es'
                        ? 'Idioma cambiado a Español.'
                        : 'Language switched to English.',
                );
            },
        },
    );
}

// ── Interface Preferences (persisted in users.settings JSON) ──
const userSettings = computed(() => page.props.auth.user?.settings ?? {});
const sidebarPosition = ref(userSettings.value.sidebar_position || 'leading');
const textWeight = ref(userSettings.value.text_weight || '400');
const fabEnabled = ref(userSettings.value.fab_enabled !== false);
const visualEffectsEnabled = ref(userSettings.value.visual_effects_enabled !== false);
const iconsEnabled = ref(userSettings.value.icons_enabled !== false);

function persistSetting(key: string, value: any) {
    router.put(
        route('settings.update'),
        { [key]: value },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function setSidebarPosition(pos: 'leading' | 'trailing') {
    sidebarPosition.value = pos;
    persistSetting('sidebar_position', pos);
}

function setTextWeight(w: string) {
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
const smtpExpanded = ref(false);

const smtpForm = ref({
    host: '',
    port: 587,
    username: '',
    password: '',
    encryption: 'tls',
    from_name: '',
    from_email: '',
});

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
        // SMTP not configured yet
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
            smtpVerified.value = false; // reset verification until tested
            toast.success(t('settings.smtp_saved'));
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error((firstError as string) || t('settings.smtp_save_error'));
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
    } catch (err: any) {
        const msg = err.response?.data?.message || t('settings.smtp_test_error');
        toast.error(msg);
    } finally {
        smtpTesting.value = false;
    }
}

// ── Typography Preview Options ──
const textWeightOptions = [
    {
        value: '300',
        label: t('settings.weight_light'),
        desc: t('settings.weight_light_desc'),
        cls: 'font-light',
    },
    {
        value: '400',
        label: t('settings.weight_normal'),
        desc: t('settings.weight_normal_desc'),
        cls: 'font-normal',
    },
    {
        value: '500',
        label: t('settings.weight_medium'),
        desc: t('settings.weight_medium_desc'),
        cls: 'font-medium',
    },
    {
        value: '600',
        label: t('settings.weight_strong'),
        desc: t('settings.weight_strong_desc'),
        cls: 'font-semibold',
    },
];

const currentPreviewClass = computed(() => {
    switch (textWeight.value) {
        case '300':
            return 'font-light';
        case '500':
            return 'font-medium';
        case '600':
            return 'font-semibold';
        default:
            return 'font-normal';
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('settings.title')" />

        <div class="space-y-6">
            <!-- Header card with restful pedagogical gradient -->
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                            <Settings3Regular class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                                {{ t('settings.title') }}
                            </h1>
                            <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5 leading-relaxed">
                                {{ t('settings.pedagogical_tip') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white/80 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 border border-cafe-200/80 dark:border-cafe-700 shadow-2xs">
                            <SparklesRegular class="w-3.5 h-3.5 text-accent-600 dark:text-accent-400" />
                            <span>{{ t('settings.environment_role') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- ═══ 2. MAIN TWO-COLUMN GRID ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
                <!-- ── LEFT COLUMN (3/5 = 60%): Appearance, Language, Integrations ── -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- SECTION 1: Appearance (Theme Mode) -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs space-y-5">
                        <div class="flex items-center gap-2.5 border-b border-cafe-100 dark:border-surface-dark-3 pb-4">
                            <div class="w-8 h-8 rounded-lg bg-cafe-100 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 flex items-center justify-center shrink-0">
                                <SunRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-cafe-900 dark:text-cafe-100">
                                    {{ t('settings.appearance') }}
                                </h2>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                    {{ t('settings.theme_description') }}
                                </p>
                            </div>
                        </div>

                        <!-- 3-way Interactive Theme Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                            <!-- Modo Claro -->
                            <button
                                type="button"
                                @click="setMode('light')"
                                class="relative flex flex-col items-center p-4 rounded-xl border text-center transition-all duration-150 group"
                                :class="mode === 'light'
                                    ? 'border-accent-500 bg-accent-50/40 dark:bg-accent-950/20 shadow-xs ring-1 ring-accent-500'
                                    : 'border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40 hover:bg-cafe-100/60 dark:hover:bg-surface-dark-2 hover:border-cafe-300'"
                            >
                                <span
                                    v-if="mode === 'light'"
                                    class="absolute top-2.5 right-2.5 w-4 h-4 rounded-full bg-accent-500 text-white flex items-center justify-center shadow-xs"
                                >
                                    <CheckRegular class="w-2.5 h-2.5" />
                                </span>
                                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-surface-dark-3 text-amber-700 dark:text-amber-400 flex items-center justify-center mb-2.5 transition-transform group-hover:scale-105">
                                    <SunRegular class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold text-cafe-900 dark:text-cafe-100 mb-0.5">
                                    {{ t('theme.light') }}
                                </span>
                                <span class="text-[11px] text-cafe-500 dark:text-cafe-400 leading-tight">
                                    {{ t('settings.theme_light_desc') }}
                                </span>
                            </button>

                            <!-- Modo Oscuro -->
                            <button
                                type="button"
                                @click="setMode('dark')"
                                class="relative flex flex-col items-center p-4 rounded-xl border text-center transition-all duration-150 group"
                                :class="mode === 'dark'
                                    ? 'border-accent-500 bg-accent-50/40 dark:bg-accent-950/20 shadow-xs ring-1 ring-accent-500'
                                    : 'border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40 hover:bg-cafe-100/60 dark:hover:bg-surface-dark-2 hover:border-cafe-300'"
                            >
                                <span
                                    v-if="mode === 'dark'"
                                    class="absolute top-2.5 right-2.5 w-4 h-4 rounded-full bg-accent-500 text-white flex items-center justify-center shadow-xs"
                                >
                                    <CheckRegular class="w-2.5 h-2.5" />
                                </span>
                                <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-surface-dark-3 text-indigo-700 dark:text-indigo-400 flex items-center justify-center mb-2.5 transition-transform group-hover:scale-105">
                                    <MoonRegular class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold text-cafe-900 dark:text-cafe-100 mb-0.5">
                                    {{ t('theme.dark') }}
                                </span>
                                <span class="text-[11px] text-cafe-500 dark:text-cafe-400 leading-tight">
                                    {{ t('settings.theme_dark_desc') }}
                                </span>
                            </button>

                            <!-- Sistema -->
                            <button
                                type="button"
                                @click="setMode('system')"
                                class="relative flex flex-col items-center p-4 rounded-xl border text-center transition-all duration-150 group"
                                :class="mode === 'system'
                                    ? 'border-accent-500 bg-accent-50/40 dark:bg-accent-950/20 shadow-xs ring-1 ring-accent-500'
                                    : 'border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40 hover:bg-cafe-100/60 dark:hover:bg-surface-dark-2 hover:border-cafe-300'"
                            >
                                <span
                                    v-if="mode === 'system'"
                                    class="absolute top-2.5 right-2.5 w-4 h-4 rounded-full bg-accent-500 text-white flex items-center justify-center shadow-xs"
                                >
                                    <CheckRegular class="w-2.5 h-2.5" />
                                </span>
                                <div class="w-10 h-10 rounded-xl bg-cafe-200/70 dark:bg-surface-dark-3 text-cafe-700 dark:text-cafe-300 flex items-center justify-center mb-2.5 transition-transform group-hover:scale-105">
                                    <ComputerRegular class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold text-cafe-900 dark:text-cafe-100 mb-0.5">
                                    {{ t('theme.system') }}
                                </span>
                                <span class="text-[11px] text-cafe-500 dark:text-cafe-400 leading-tight">
                                    {{ t('settings.theme_system_desc') }}
                                </span>
                            </button>
                        </div>
                    </section>

                    <!-- SECTION 2: Language & Regionalization -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs space-y-5">
                        <div class="flex items-center gap-2.5 border-b border-cafe-100 dark:border-surface-dark-3 pb-4">
                            <div class="w-8 h-8 rounded-lg bg-cafe-100 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 flex items-center justify-center shrink-0">
                                <Translate2Regular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-cafe-900 dark:text-cafe-100">
                                    {{ t('settings.language') }}
                                </h2>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                    {{ t('settings.language_description') }}
                                </p>
                            </div>
                        </div>

                        <!-- 2-card Language Selector -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <!-- Español -->
                            <button
                                type="button"
                                @click="switchLocale('es')"
                                class="relative flex items-center justify-between p-4 rounded-xl border text-left transition-all duration-150"
                                :class="locale === 'es'
                                    ? 'border-accent-500 bg-accent-50/40 dark:bg-accent-950/20 shadow-xs ring-1 ring-accent-500'
                                    : 'border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40 hover:bg-cafe-100/60 dark:hover:bg-surface-dark-2 hover:border-cafe-300'"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl font-mono text-xs font-bold bg-amber-100 dark:bg-surface-dark-3 text-amber-800 dark:text-amber-300 flex items-center justify-center shrink-0 border border-amber-200/60 dark:border-surface-dark-2">
                                        ES
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                            Español
                                        </p>
                                        <p class="text-[11px] text-cafe-500 dark:text-cafe-400">
                                            Castellano (Latinoamérica / Internacional)
                                        </p>
                                    </div>
                                </div>
                                <span
                                    v-if="locale === 'es'"
                                    class="w-5 h-5 rounded-full bg-accent-500 text-white flex items-center justify-center shrink-0 shadow-xs"
                                >
                                    <CheckRegular class="w-3 h-3" />
                                </span>
                            </button>

                            <!-- English -->
                            <button
                                type="button"
                                @click="switchLocale('en')"
                                class="relative flex items-center justify-between p-4 rounded-xl border text-left transition-all duration-150"
                                :class="locale === 'en'
                                    ? 'border-accent-500 bg-accent-50/40 dark:bg-accent-950/20 shadow-xs ring-1 ring-accent-500'
                                    : 'border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40 hover:bg-cafe-100/60 dark:hover:bg-surface-dark-2 hover:border-cafe-300'"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl font-mono text-xs font-bold bg-blue-100 dark:bg-surface-dark-3 text-blue-800 dark:text-blue-300 flex items-center justify-center shrink-0 border border-blue-200/60 dark:border-surface-dark-2">
                                        EN
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                            English
                                        </p>
                                        <p class="text-[11px] text-cafe-500 dark:text-cafe-400">
                                            English (International / US)
                                        </p>
                                    </div>
                                </div>
                                <span
                                    v-if="locale === 'en'"
                                    class="w-5 h-5 rounded-full bg-accent-500 text-white flex items-center justify-center shrink-0 shadow-xs"
                                >
                                    <CheckRegular class="w-3 h-3" />
                                </span>
                            </button>
                        </div>
                    </section>

                    <!-- SECTION 3: Integrations — Email (SMTP) -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs space-y-5">
                        <div class="flex items-start justify-between gap-3 border-b border-cafe-100 dark:border-surface-dark-3 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-cafe-100 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 flex items-center justify-center shrink-0">
                                    <MailRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ t('settings.smtp_title') }}
                                    </h2>
                                    <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                        {{ t('settings.smtp_description') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Live SMTP Status Badge -->
                            <div>
                                <span
                                    v-if="smtpVerified"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-800/50"
                                >
                                    <CheckCircleRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('settings.smtp_verified') }}</span>
                                </span>
                                <span
                                    v-else-if="smtpConfigured"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200/70 dark:border-amber-800/40"
                                >
                                    <AlertRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('settings.smtp_not_verified') }}</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-cafe-100 dark:bg-surface-dark-2 text-cafe-600 dark:text-cafe-400 border border-cafe-200/60 dark:border-surface-dark-3"
                                >
                                    <span>{{ t('settings.smtp_not_configured') }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Expand/Collapse Drawer Trigger Button -->
                        <button
                            type="button"
                            @click="smtpExpanded = !smtpExpanded"
                            class="w-full flex items-center justify-between p-3.5 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40 hover:bg-cafe-100/60 dark:hover:bg-surface-dark-2 text-xs font-bold text-cafe-800 dark:text-cafe-200 transition-colors group"
                        >
                            <span class="flex items-center gap-2">
                                <ServerRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                                <span>{{ smtpExpanded ? t('settings.smtp_collapse') : t('settings.smtp_expand') }}</span>
                            </span>
                            <DownSmallRegular
                                v-if="smtpExpanded"
                                class="w-4 h-4 text-cafe-400 transition-transform"
                            />
                            <RightSmallRegular
                                v-else
                                class="w-4 h-4 text-cafe-400 group-hover:translate-x-0.5 transition-transform"
                            />
                        </button>

                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 -translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-2"
                        >
                            <div v-if="smtpExpanded" class="space-y-4 pt-2">
                                <div v-if="smtpLoading" class="text-xs text-cafe-500 dark:text-cafe-400 py-6 text-center flex items-center justify-center gap-2">
                                    <Refresh1Regular class="w-4 h-4 animate-spin text-accent-600" />
                                    <span>{{ t('settings.smtp_loading') }}</span>
                                </div>

                                <template v-else>
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

                                    <!-- Encryption Selector Pills -->
                                    <div>
                                        <span class="block text-xs font-semibold text-cafe-700 dark:text-cafe-200 mb-2">
                                            {{ t('settings.smtp_encryption') }}
                                        </span>
                                        <div class="inline-flex rounded-xl border border-cafe-200/80 dark:border-surface-dark-3 bg-cafe-50 dark:bg-surface-dark-2 p-1 gap-1">
                                            <button
                                                v-for="opt in ['tls', 'ssl', 'none']"
                                                :key="opt"
                                                type="button"
                                                @click="smtpForm.encryption = opt"
                                                class="px-3.5 py-1 text-xs font-bold rounded-lg transition-all"
                                                :class="smtpForm.encryption === opt
                                                    ? 'bg-accent-500 text-white shadow-xs'
                                                    : 'text-cafe-600 dark:text-cafe-300 hover:text-cafe-900 dark:hover:text-cafe-100'"
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
                                            placeholder="tu@colegio.edu"
                                        />
                                    </div>

                                    <!-- Helper Hint Box -->
                                    <div class="rounded-xl bg-cafe-50/80 dark:bg-surface-dark-2/60 border border-cafe-200/60 dark:border-surface-dark-3 p-3.5 text-xs text-cafe-600 dark:text-cafe-300 space-y-1.5">
                                        <div class="flex items-start gap-2">
                                            <ServerRegular class="w-4 h-4 text-accent-600 dark:text-accent-400 shrink-0 mt-0.5" />
                                            <div>
                                                <p><strong class="text-cafe-800 dark:text-cafe-200">Gmail:</strong> {{ t('settings.smtp_gmail_hint') }}</p>
                                                <p class="mt-0.5"><strong class="text-cafe-800 dark:text-cafe-200">Outlook:</strong> {{ t('settings.smtp_outlook_hint') }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-3 pt-2">
                                        <CpButton
                                            @click="saveSmtp"
                                            :disabled="smtpSaving"
                                            :loading="smtpSaving"
                                        >
                                            {{ smtpSaving ? t('settings.saving') : t('settings.save') }}
                                        </CpButton>

                                        <CpButton
                                            variant="secondary"
                                            @click="testSmtp"
                                            :disabled="smtpTesting || !smtpConfigured"
                                            :loading="smtpTesting"
                                        >
                                            <SendPlaneRegular class="w-4 h-4 mr-1.5" />
                                            <span>{{ smtpTesting ? t('settings.smtp_testing') : t('settings.smtp_test') }}</span>
                                        </CpButton>
                                    </div>
                                </template>
                            </div>
                        </Transition>
                    </section>
                </div>

                <!-- ── RIGHT COLUMN (2/5 = 40%): Interface & Ergonomics ── -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- SECTION 4: Interface & Ergonomics Card -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs space-y-6">
                        <div class="flex items-center gap-2.5 border-b border-cafe-100 dark:border-surface-dark-3 pb-4">
                            <div class="w-8 h-8 rounded-lg bg-cafe-100 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 flex items-center justify-center shrink-0">
                                <Layout2Regular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-cafe-900 dark:text-cafe-100">
                                    {{ t('settings.interface') }}
                                </h2>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                    {{ t('settings.interface_description') }}
                                </p>
                            </div>
                        </div>

                        <!-- 1. Sidebar Position Visual Mini-Cards -->
                        <div class="space-y-3">
                            <span class="block text-xs font-bold text-cafe-800 dark:text-cafe-200">
                                {{ t('settings.sidebar_position') }}
                            </span>

                            <div class="grid grid-cols-2 gap-3">
                                <!-- Izquierda (Leading) -->
                                <button
                                    type="button"
                                    @click="setSidebarPosition('leading')"
                                    class="relative p-3 rounded-xl border text-left transition-all duration-150 flex flex-col justify-between h-24"
                                    :class="sidebarPosition === 'leading'
                                        ? 'border-accent-500 bg-accent-50/40 dark:bg-accent-950/20 ring-1 ring-accent-500 shadow-xs'
                                        : 'border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40 hover:bg-cafe-100/60 dark:hover:bg-surface-dark-2'"
                                >
                                    <!-- Wireframe icon representation -->
                                    <div class="flex items-center justify-between w-full">
                                        <div class="w-8 h-6 rounded-sm border border-cafe-300 dark:border-cafe-600 flex overflow-hidden">
                                            <div class="w-2.5 h-full bg-accent-500" />
                                            <div class="grow h-full bg-cafe-100 dark:bg-surface-dark-3" />
                                        </div>
                                        <span
                                            v-if="sidebarPosition === 'leading'"
                                            class="w-4 h-4 rounded-full bg-accent-500 text-white flex items-center justify-center"
                                        >
                                            <CheckRegular class="w-2.5 h-2.5" />
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-cafe-900 dark:text-cafe-100">
                                            {{ t('settings.leading') }}
                                        </span>
                                        <span class="block text-[10px] text-cafe-500 dark:text-cafe-400">
                                            {{ t('settings.sidebar_leading_desc') }}
                                        </span>
                                    </div>
                                </button>

                                <!-- Derecha (Trailing) -->
                                <button
                                    type="button"
                                    @click="setSidebarPosition('trailing')"
                                    class="relative p-3 rounded-xl border text-left transition-all duration-150 flex flex-col justify-between h-24"
                                    :class="sidebarPosition === 'trailing'
                                        ? 'border-accent-500 bg-accent-50/40 dark:bg-accent-950/20 ring-1 ring-accent-500 shadow-xs'
                                        : 'border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40 hover:bg-cafe-100/60 dark:hover:bg-surface-dark-2'"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <div class="w-8 h-6 rounded-sm border border-cafe-300 dark:border-cafe-600 flex overflow-hidden">
                                            <div class="grow h-full bg-cafe-100 dark:bg-surface-dark-3" />
                                            <div class="w-2.5 h-full bg-accent-500" />
                                        </div>
                                        <span
                                            v-if="sidebarPosition === 'trailing'"
                                            class="w-4 h-4 rounded-full bg-accent-500 text-white flex items-center justify-center"
                                        >
                                            <CheckRegular class="w-2.5 h-2.5" />
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-cafe-900 dark:text-cafe-100">
                                            {{ t('settings.trailing') }}
                                        </span>
                                        <span class="block text-[10px] text-cafe-500 dark:text-cafe-400">
                                            {{ t('settings.sidebar_trailing_desc') }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-cafe-100 dark:border-surface-dark-3" />

                        <!-- 2. Typography Weight & Live Preview -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <FontRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                                <span class="text-xs font-bold text-cafe-800 dark:text-cafe-200">
                                    {{ t('settings.text_weight') }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <label
                                    v-for="opt in textWeightOptions"
                                    :key="opt.value"
                                    class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                    :class="textWeight === opt.value
                                        ? 'border-accent-500 bg-accent-50/40 dark:bg-accent-950/20 shadow-2xs ring-1 ring-accent-500'
                                        : 'border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/30 dark:bg-surface-dark-2/30 hover:bg-cafe-100/50 dark:hover:bg-surface-dark-2/60'"
                                >
                                    <span
                                        class="flex items-center justify-center w-4 h-4 rounded-full border-2 transition-colors shrink-0"
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
                                    <div class="grow">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-cafe-900 dark:text-cafe-100" :class="opt.cls">
                                                {{ opt.label }}
                                            </span>
                                            <span class="text-[10px] font-mono font-bold text-cafe-400">
                                                {{ opt.value }}
                                            </span>
                                        </div>
                                        <span class="block text-[11px] text-cafe-500 dark:text-cafe-400">
                                            {{ opt.desc }}
                                        </span>
                                    </div>
                                </label>
                            </div>

                            <!-- Live Typography Sample Preview Box -->
                            <div class="p-3.5 rounded-xl bg-cafe-100/60 dark:bg-surface-dark-2/50 border border-cafe-200/60 dark:border-surface-dark-3 space-y-1">
                                <span class="text-[10px] font-bold text-cafe-500 dark:text-cafe-400 uppercase tracking-wider block">
                                    {{ t('settings.preview_sample_label') }}
                                </span>
                                <p class="text-xs text-cafe-900 dark:text-cafe-100 leading-relaxed transition-all" :class="currentPreviewClass">
                                    {{ t('settings.preview_sample_text') }}
                                </p>
                            </div>
                        </div>

                        <div class="border-t border-cafe-100 dark:border-surface-dark-3" />

                        <!-- 3. Ergonomic Feature Toggles -->
                        <div class="space-y-4">
                            <!-- FAB Inteligente -->
                            <div class="flex items-start justify-between gap-4 p-3 rounded-xl border border-cafe-200/60 dark:border-surface-dark-3 bg-cafe-50/30 dark:bg-surface-dark-2/30">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-6 h-6 rounded-lg bg-amber-100 dark:bg-surface-dark-3 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <SparklesRegular class="w-3.5 h-3.5" />
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-cafe-800 dark:text-cafe-200">
                                            {{ t('settings.fab') }}
                                        </span>
                                        <span class="text-[11px] text-cafe-500 dark:text-cafe-400 mt-0.5 block leading-normal">
                                            {{ t('settings.fab_description') }}
                                        </span>
                                    </div>
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
                                        class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-xs ring-0 transition-transform duration-200 ease-in-out"
                                        :class="fabEnabled ? 'translate-x-5' : 'translate-x-0'"
                                    />
                                </button>
                            </div>

                            <!-- Efectos Visuales -->
                            <div class="flex items-start justify-between gap-4 p-3 rounded-xl border border-cafe-200/60 dark:border-surface-dark-3 bg-cafe-50/30 dark:bg-surface-dark-2/30">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-6 h-6 rounded-lg bg-purple-100 dark:bg-surface-dark-3 text-purple-700 dark:text-purple-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <PaletteRegular class="w-3.5 h-3.5" />
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-cafe-800 dark:text-cafe-200">
                                            {{ t('settings.visual_effects') }}
                                        </span>
                                        <span class="text-[11px] text-cafe-500 dark:text-cafe-400 mt-0.5 block leading-normal">
                                            {{ t('settings.visual_effects_description') }}
                                        </span>
                                    </div>
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
                                        class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-xs ring-0 transition-transform duration-200 ease-in-out"
                                        :class="visualEffectsEnabled ? 'translate-x-5' : 'translate-x-0'"
                                    />
                                </button>
                            </div>

                            <!-- Iconos Decorativos -->
                            <div class="flex items-start justify-between gap-4 p-3 rounded-xl border border-cafe-200/60 dark:border-surface-dark-3 bg-cafe-50/30 dark:bg-surface-dark-2/30">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-surface-dark-3 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <EyeRegular class="w-3.5 h-3.5" />
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-cafe-800 dark:text-cafe-200">
                                            {{ t('settings.icons_enabled') }}
                                        </span>
                                        <span class="text-[11px] text-cafe-500 dark:text-cafe-400 mt-0.5 block leading-normal">
                                            {{ t('settings.icons_enabled_description') }}
                                        </span>
                                    </div>
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
                                        class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-xs ring-0 transition-transform duration-200 ease-in-out"
                                        :class="iconsEnabled ? 'translate-x-5' : 'translate-x-0'"
                                    />
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- SECTION 5: Native Desktop IPC Status (Tauri only) -->
                    <section
                        v-if="isTauri && tauriSystemInfo"
                        class="rounded-2xl border border-accent-200/80 dark:border-accent-800/60 bg-gradient-to-br from-white via-cafe-50/40 to-accent-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/40 dark:to-surface-dark-3/20 p-5 shadow-xs space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" />
                                <h3 class="text-xs font-bold text-cafe-900 dark:text-cafe-100 uppercase tracking-wider">
                                    {{ t('desktop.status') }}
                                </h3>
                            </div>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-accent-100 dark:bg-accent-950 text-accent-700 dark:text-accent-300 font-semibold">
                                {{ tauriSystemInfo.platform }} · {{ tauriSystemInfo.arch }}
                            </span>
                        </div>
                        <p class="text-xs text-cafe-600 dark:text-cafe-300 leading-relaxed">
                            {{ t('desktop.connected') }} (v{{ tauriSystemInfo.app_version }})
                            <span v-if="tauriHealth" class="ml-1 text-emerald-600 font-mono font-medium">
                                · Ping: {{ tauriHealth.latency_ms }}ms (127.0.0.1:{{ tauriHealth.port }})
                            </span>
                        </p>
                    </section>
                </div>
            </div>

            <!-- ═══ 3. BACKUPS & DATA PROTECTION (Full Width) ═══ -->
            <section id="backups" class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-cafe-100 dark:border-surface-dark-3 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 flex items-center justify-center shrink-0 border border-amber-200/60">
                            <ShieldRegular class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-cafe-900 dark:text-cafe-100">
                                    {{ t('backup.title') }}
                                </h2>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60">
                                    {{ t('backup.badge') }}
                                </span>
                            </div>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                {{ t('backup.subtitle') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            v-if="isTauri && tauriAppPaths?.backups_dir"
                            type="button"
                            @click="handleOpenBackupsFolder"
                            class="px-3 py-1.5 rounded-xl text-xs font-medium text-cafe-700 dark:text-cafe-300 hover:bg-cafe-100 dark:hover:bg-surface-dark-2 border border-cafe-200 dark:border-surface-dark-3 transition-colors flex items-center gap-1.5"
                            :title="t('backup.open_folder')"
                        >
                            <Folder2Regular class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                            <span>{{ t('backup.open_folder') }}</span>
                        </button>
                        <button
                            v-if="props.backups.length > 0"
                            type="button"
                            :disabled="isPruningBackups"
                            @click="handlePruneBackups"
                            class="px-3 py-1.5 rounded-xl text-xs font-medium text-cafe-600 dark:text-cafe-400 hover:bg-cafe-100 dark:hover:bg-surface-dark-2 border border-cafe-200 dark:border-surface-dark-3 transition-colors"
                        >
                            {{ isPruningBackups ? t('backup.pruning') : t('backup.prune') }}
                        </button>
                    </div>
                </div>

                <!-- Create Backup Controls -->
                <div class="rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40 p-4 sm:p-5 space-y-3">
                    <h3 class="text-xs font-bold text-cafe-800 dark:text-cafe-200 uppercase tracking-wider">
                        {{ t('backup.create') }}
                    </h3>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                        <div class="flex-1 space-y-1">
                            <label class="block text-xs font-medium text-cafe-700 dark:text-cafe-300">
                                {{ t('backup.password') }}
                            </label>
                            <div class="relative">
                                <Key1Regular class="w-4 h-4 text-cafe-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none z-10" />
                                <input
                                    v-model="createPassword"
                                    type="password"
                                    :placeholder="t('backup.password_hint')"
                                    class="cp-input h-10 w-full pl-9 pr-3 text-xs"
                                />
                            </div>
                        </div>
                        <CpButton
                            type="button"
                            variant="primary"
                            :disabled="isCreatingBackup"
                            @click="handleCreateBackup"
                            class="h-10 sm:shrink-0 flex items-center justify-center px-4"
                        >
                            <ShieldRegular class="w-4 h-4 mr-1.5" />
                            <span>{{ isCreatingBackup ? t('backup.creating') : t('backup.create') }}</span>
                        </CpButton>
                    </div>
                </div>

                <!-- Backup Snapshots Table -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-cafe-800 dark:text-cafe-200 uppercase tracking-wider">
                        {{ t('backup.history_title', { count: props.backups.length }) }}
                    </h3>

                    <div v-if="props.backups.length === 0" class="rounded-xl border border-dashed border-cafe-200 dark:border-surface-dark-3 p-8 text-center text-xs text-cafe-500">
                        {{ t('backup.empty') }}
                    </div>

                    <div v-else class="overflow-x-auto rounded-xl border border-cafe-200/80 dark:border-surface-dark-3">
                        <table class="w-full text-xs text-left">
                            <thead>
                                <tr class="bg-cafe-100/70 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300 border-b border-cafe-200 dark:border-surface-dark-3">
                                    <th class="px-4 py-3 font-semibold">{{ t('backup.col_filename') }}</th>
                                    <th class="px-4 py-3 font-semibold">{{ t('backup.col_size') }}</th>
                                    <th class="px-4 py-3 font-semibold">{{ t('backup.col_date') }}</th>
                                    <th class="px-4 py-3 font-semibold text-right">{{ t('backup.col_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="backup in props.backups"
                                    :key="backup.filename"
                                    class="border-b border-cafe-100 dark:border-surface-dark-3 last:border-0 hover:bg-cafe-50/60 dark:hover:bg-surface-dark-2/50 transition-colors"
                                >
                                    <td class="px-4 py-3 font-mono font-medium text-cafe-900 dark:text-cafe-100 flex items-center gap-2">
                                        <Folder2Regular class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                                        <span>{{ backup.filename }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-cafe-600 dark:text-cafe-400 font-mono">
                                        {{ formatBytes(backup.size) }}
                                    </td>
                                    <td class="px-4 py-3 text-cafe-600 dark:text-cafe-400">
                                        {{ backup.created_at ? new Date(backup.created_at).toLocaleString() : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="handleDownloadBackup(backup)"
                                                class="px-2.5 py-1 rounded-lg text-xs font-medium text-cafe-700 dark:text-cafe-300 hover:bg-cafe-200 dark:hover:bg-surface-dark-3 border border-cafe-200 dark:border-surface-dark-3 transition-colors flex items-center gap-1"
                                                :title="t('backup.download')"
                                            >
                                                <DownloadRegular class="w-3.5 h-3.5" />
                                                <span class="hidden sm:inline">{{ t('backup.download') }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openRestoreModal(backup)"
                                                class="px-2.5 py-1 rounded-lg text-xs font-semibold text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/40 border border-amber-200/60 dark:border-amber-800/60 transition-colors flex items-center gap-1"
                                                :title="t('backup.restore')"
                                            >
                                                <Refresh1Regular class="w-3.5 h-3.5" />
                                                <span>{{ t('backup.restore') }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="handleDeleteBackup(backup)"
                                                class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors"
                                                :title="t('backup.delete')"
                                            >
                                                <Delete2Regular class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ═══ 4. RESTORE CONFIRMATION MODAL ═══ -->
            <div v-if="showRestoreModal && restoreTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
                <div class="rounded-2xl border border-cafe-200 dark:border-surface-dark-3 bg-white dark:bg-surface-dark-1 max-w-md w-full p-6 shadow-xl space-y-4">
                    <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
                        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-950/60 flex items-center justify-center shrink-0">
                            <AlertRegular class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-cafe-900 dark:text-cafe-50">
                                {{ t('backup.restore') }}
                            </h3>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                {{ restoreTarget.filename }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-cafe-600 dark:text-cafe-300 leading-relaxed">
                        {{ t('backup.confirm_restore') }}
                    </p>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-cafe-700 dark:text-cafe-300">
                            {{ t('backup.password') }}
                        </label>
                        <CpInput
                            v-model="restorePassword"
                            type="password"
                            :placeholder="t('backup.password_hint')"
                            class="w-full text-xs"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-cafe-100 dark:border-surface-dark-3">
                        <CpButton
                            variant="ghost"
                            :disabled="isRestoringBackup"
                            @click="closeRestoreModal"
                        >
                            {{ t('backup.cancel') }}
                        </CpButton>
                        <CpButton
                            variant="primary"
                            :disabled="isRestoringBackup"
                            @click="handleConfirmRestore"
                            class="bg-red-600 hover:bg-red-700 text-white"
                        >
                            {{ isRestoringBackup ? t('backup.restoring') : t('backup.confirm_restore_action') }}
                        </CpButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
