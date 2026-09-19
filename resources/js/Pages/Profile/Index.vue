<script setup lang="ts">
/**
 * Profile Index — /perfil
 * Unified with the Café Pedagógico Design System:
 * - Educator Identity Card with initials pseudo-avatar, adscripción, and role badge
 * - Professional Information (Read mode with structured cards, Edit mode with refined inputs)
 * - Security & Recovery Vault:
 *   1. Password change with inline strength guidance
 *   2. Two-Factor Authentication (2FA / MFA) with autonomous SVG QR setup, manual key copy, and verification
 *   3. Recovery Codes Vault with interactive password-guarded regeneration, instant copy, and download (.txt)
 * - Pedagogical contextual guidance on how profile data enriches academic export documents
 */

import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertRegular,
    AwardRegular,
    Book2Regular,
    Building1Regular,
    BulbRegular,
    CheckCircleRegular,
    CloseCircleRegular,
    Copy2Regular,
    Download2Regular,
    Edit3Regular,
    Key1Regular,
    LockRegular,
    MailRegular,
    QrcodeRegular,
    Refresh1Regular,
    SafeShield2Regular,
    ShieldShapeRegular,
    User1Regular,
    UserHeartRegular,
    UserSecurityRegular,
} from '@mingcute/vue/core-regular';
import axios from 'axios';
import { computed, ref } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import CpSelect from '@/Components/CpSelect.vue';
import { useToast } from '@/composables/useToast';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const { t } = useTranslations();
const toast = useToast();
const page = usePage();

interface UserProfileData {
    first_name: string;
    last_name: string;
    full_name?: string;
    initials?: string;
    email: string;
    pronoun?: string | null;
    institution?: string | null;
    educational_area?: string | null;
    educational_level?: string | null;
    unused_recovery_codes_count?: number;
    two_factor_enabled?: boolean;
    two_factor_confirmed_at?: string | null;
}

const props = defineProps<{
    user: UserProfileData;
}>();

const editing = ref(false);

// Local reactive state for dynamic updates without full page reload
const isTwoFactorEnabled = ref(props.user.two_factor_enabled ?? false);
const twoFactorConfirmedDate = ref(props.user.two_factor_confirmed_at ?? null);
const recoveryCodesCount = ref(props.user.unused_recovery_codes_count ?? 0);

// ── Profile Form ──
const profileForm = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    email: props.user.email,
    institution: props.user.institution || '',
    pronoun: props.user.pronoun || '',
    educational_area: props.user.educational_area || '',
    educational_level: props.user.educational_level || '',
});

function startEditing() {
    editing.value = true;
}

function cancelEditing() {
    profileForm.reset();
    profileForm.clearErrors();
    editing.value = false;
}

function submitProfile() {
    profileForm.put(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
            toast.success(t('profile.profile_updated_success'));
        },
    });
}

// ── Password Form ──
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submitPassword() {
    passwordForm.put(route('profile.update-password'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            passwordForm.clearErrors();
            toast.success(t('profile.password_updated_success'));
        },
    });
}

// ── 2FA State & Methods ──
const show2faSetupModal = ref(false);
const show2faDisableModal = ref(false);
const loadingSetup = ref(false);
const confirming2fa = ref(false);
const disabling2fa = ref(false);

const twoFactorSetupData = ref<{ secret: string; qr_svg: string } | null>(null);
const twoFactorCode = ref('');
const twoFactorCodeError = ref('');

const disablePassword = ref('');
const disablePasswordError = ref('');

async function open2faSetup() {
    loadingSetup.value = true;
    show2faSetupModal.value = true;
    twoFactorCode.value = '';
    twoFactorCodeError.value = '';
    twoFactorSetupData.value = null;

    try {
        const response = await axios.post(route('two-factor.setup'));
        twoFactorSetupData.value = response.data;
    } catch {
        toast.error(t('profile.two_factor_setup_expired'));
        show2faSetupModal.value = false;
    } finally {
        loadingSetup.value = false;
    }
}

function close2faSetup() {
    show2faSetupModal.value = false;
    twoFactorCode.value = '';
    twoFactorCodeError.value = '';
    twoFactorSetupData.value = null;
}

function copySecretKey() {
    if (!twoFactorSetupData.value?.secret) return;
    navigator.clipboard.writeText(twoFactorSetupData.value.secret);
    toast.success(t('profile.key_copied'));
}

async function confirm2fa() {
    if (twoFactorCode.value?.length !== 6) {
        twoFactorCodeError.value = t('profile.code_required');
        return;
    }

    confirming2fa.value = true;
    twoFactorCodeError.value = '';

    try {
        const response = await axios.post(route('two-factor.confirm'), {
            code: twoFactorCode.value,
        });

        isTwoFactorEnabled.value = true;
        const now = new Date();
        twoFactorConfirmedDate.value = now.toLocaleDateString();
        close2faSetup();
        toast.success(response.data.message || t('auth.two_factor_enabled_success'));
    } catch (err: any) {
        if (err.response?.data?.errors?.code) {
            twoFactorCodeError.value = err.response.data.errors.code[0];
        } else {
            twoFactorCodeError.value = t('auth.two_factor_code_invalid');
        }
    } finally {
        confirming2fa.value = false;
    }
}

function open2faDisable() {
    disablePassword.value = '';
    disablePasswordError.value = '';
    show2faDisableModal.value = true;
}

function close2faDisable() {
    show2faDisableModal.value = false;
    disablePassword.value = '';
    disablePasswordError.value = '';
}

async function confirmDisable2fa() {
    if (!disablePassword.value) {
        disablePasswordError.value = t('field.password');
        return;
    }

    disabling2fa.value = true;
    disablePasswordError.value = '';

    try {
        const response = await axios.delete(route('two-factor.disable'), {
            data: { password: disablePassword.value },
        });

        isTwoFactorEnabled.value = false;
        twoFactorConfirmedDate.value = null;
        close2faDisable();
        toast.success(response.data.message || t('auth.two_factor_disabled_success'));
    } catch (err: any) {
        if (err.response?.data?.errors?.password) {
            disablePasswordError.value = err.response.data.errors.password[0];
        } else {
            disablePasswordError.value = t('auth.password');
        }
    } finally {
        disabling2fa.value = false;
    }
}

// ── Recovery Codes Vault State & Methods ──
const showRegenerateModal = ref(false);
const showVaultDisplayModal = ref(false);
const regeneratePassword = ref('');
const regeneratePasswordError = ref('');
const regeneratingCodes = ref(false);
const newRecoveryCodes = ref<string[]>([]);
const downloadContent = ref('');

function openRegenerateModal() {
    regeneratePassword.value = '';
    regeneratePasswordError.value = '';
    showRegenerateModal.value = true;
}

function closeRegenerateModal() {
    showRegenerateModal.value = false;
    regeneratePassword.value = '';
    regeneratePasswordError.value = '';
}

async function confirmRegenerateCodes() {
    if (!regeneratePassword.value) {
        regeneratePasswordError.value = t('field.password');
        return;
    }

    regeneratingCodes.value = true;
    regeneratePasswordError.value = '';

    try {
        const response = await axios.post(
            route('recovery-codes.regenerate'),
            { password: regeneratePassword.value },
            { headers: { Accept: 'application/json' } },
        );

        newRecoveryCodes.value = response.data.codes || [];
        downloadContent.value = response.data.download_content || '';
        recoveryCodesCount.value = response.data.remaining_count ?? 6;

        closeRegenerateModal();
        showVaultDisplayModal.value = true;
        toast.success(t('auth.recovery_codes_regenerated_success'));
    } catch (err: any) {
        if (err.response?.data?.errors?.password) {
            regeneratePasswordError.value = err.response.data.errors.password[0];
        } else {
            regeneratePasswordError.value = t('auth.password');
        }
    } finally {
        regeneratingCodes.value = false;
    }
}

function copyAllCodes() {
    if (!newRecoveryCodes.value.length) return;
    const formatted = newRecoveryCodes.value.map((c, i) => `${i + 1}. ${c}`).join('\n');
    navigator.clipboard.writeText(formatted);
    toast.success(t('profile.all_codes_copied'));
}

function downloadCodesFile() {
    const content = downloadContent.value || newRecoveryCodes.value.join('\n');
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'recovery_tokens.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function closeVaultDisplay() {
    showVaultDisplayModal.value = false;
}

// ── Shared Helpers ──
const pronounOptions = [
    { value: 'él', label: t('pronoun.el') },
    { value: 'ella', label: t('pronoun.ella') },
    { value: 'elle', label: t('pronoun.elle') },
];

const profileSaved = computed(() => page.props.flash?.status === 'profile-updated');
const passwordSaved = computed(() => page.props.flash?.status === 'password-updated');

const teacherInitials = computed(() => {
    if (props.user.initials) {
        return props.user.initials;
    }
    const f = props.user.first_name ? props.user.first_name.charAt(0) : 'P';
    const l = props.user.last_name ? props.user.last_name.charAt(0) : 'D';
    return (f + l).toUpperCase();
});

const teacherFullName = computed(() => {
    return props.user.full_name || `${props.user.first_name} ${props.user.last_name}`.trim();
});

function pronounLabel(val?: string | null) {
    const opt = pronounOptions.find((o) => o.value === val);
    return opt ? opt.label : val || '—';
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('profile.title')" />

        <div class="space-y-6">
            <!-- ═══ 1. TEACHER HERO & IDENTITY CARD ═══ -->
            <section
                class="relative overflow-hidden rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-6 lg:p-8 shadow-sm"
            >
                <div class="relative z-10 space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <!-- Educator avatar & identity -->
                        <div class="flex items-center gap-5">
                            <div
                                class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-accent-500 to-amber-700 text-white font-bold text-xl sm:text-2xl flex items-center justify-center shrink-0 shadow-md border-2 border-white/50 dark:border-surface-dark-2 tracking-wider select-none font-serif"
                            >
                                {{ teacherInitials }}
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-cafe-100 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 border border-cafe-200/60 dark:border-surface-dark-3">
                                        <User1Regular class="w-3.5 h-3.5 text-accent-600 dark:text-accent-400" />
                                        <span>{{ t('profile.system_role') }}</span>
                                    </span>
                                    <span v-if="user.institution" class="text-xs text-cafe-400 dark:text-cafe-500">·</span>
                                    <span v-if="user.institution" class="text-xs font-medium text-cafe-600 dark:text-cafe-300 truncate">
                                        {{ user.institution }}
                                    </span>
                                </div>

                                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-cafe-900 dark:text-cafe-50 font-serif truncate">
                                    {{ teacherFullName }}
                                </h1>
                                <p class="text-sm text-cafe-600 dark:text-cafe-300 mt-0.5 truncate flex items-center gap-1.5">
                                    <MailRegular class="w-4 h-4 text-cafe-400" />
                                    <span>{{ user.email }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Edit Profile / Cancel action button -->
                        <div class="shrink-0 flex items-center gap-3">
                            <button
                                v-if="!editing"
                                type="button"
                                @click="startEditing"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white dark:bg-surface-dark-2 border border-cafe-200/80 dark:border-surface-dark-3 text-xs font-bold text-cafe-800 dark:text-cafe-100 hover:bg-cafe-50 dark:hover:bg-surface-dark-3 hover:border-cafe-300 transition-all shadow-xs"
                            >
                                <Edit3Regular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                                <span>{{ t('profile.edit') }}</span>
                            </button>

                            <button
                                v-else
                                type="button"
                                @click="cancelEditing"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-cafe-100 dark:bg-surface-dark-2 text-xs font-bold text-cafe-700 dark:text-cafe-300 hover:bg-cafe-200 transition-all"
                            >
                                <CloseCircleRegular class="w-4 h-4" />
                                <span>{{ t('profile.cancel_edit') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Pedagogical Focus Guidance Banner -->
                    <div class="flex items-start gap-3 p-3.5 rounded-xl bg-cafe-50/90 dark:bg-surface-dark-2/70 border border-cafe-200/60 dark:border-surface-dark-3">
                        <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                            <BulbRegular class="w-4 h-4" />
                        </div>
                        <p class="text-xs text-cafe-600 dark:text-cafe-300 leading-relaxed">
                            <span class="font-bold text-cafe-800 dark:text-cafe-200">{{ t('profile.account_details') }}: </span>
                            {{ t('profile.pedagogical_tip') }}
                        </p>
                    </div>
                </div>

                <!-- Ambient glow -->
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-amber-200/20 dark:bg-accent-500/10 rounded-full blur-3xl pointer-events-none" />
            </section>

            <!-- Success Banner -->
            <div
                v-if="profileSaved && !editing"
                class="flex items-center gap-2.5 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200/80 dark:border-emerald-800/40 text-xs font-semibold text-emerald-800 dark:text-emerald-200 shadow-xs"
            >
                <CheckCircleRegular class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span>{{ t('profile.profile_updated_success') }}</span>
            </div>

            <!-- ═══ 2. MAIN TWO-COLUMN GRID ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
                <!-- ── Left Column (3/5): Professional & Academic Information ── -->
                <div class="lg:col-span-3">
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs space-y-6">
                        <div class="flex items-center justify-between gap-4 border-b border-cafe-100 dark:border-surface-dark-3 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-cafe-100 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 flex items-center justify-center shrink-0">
                                    <User1Regular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ t('profile.personal_info') }}
                                    </h2>
                                    <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                        {{ t('profile.personal_info_description') }}
                                    </p>
                                </div>
                            </div>

                            <span v-if="editing" class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                {{ t('profile.edit') }}
                            </span>
                        </div>

                        <!-- ── READ MODE (Tile Cards) ── -->
                        <div v-if="!editing" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <!-- First Name -->
                                <div class="p-3.5 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        <User1Regular class="w-3.5 h-3.5" />
                                        <span>{{ t('field.first_name') }}</span>
                                    </div>
                                    <p class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ user.first_name }}
                                    </p>
                                </div>

                                <!-- Last Name -->
                                <div class="p-3.5 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        <User1Regular class="w-3.5 h-3.5" />
                                        <span>{{ t('field.last_name') }}</span>
                                    </div>
                                    <p class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ user.last_name }}
                                    </p>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="p-3.5 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40">
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                    <MailRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('field.email') }}</span>
                                </div>
                                <p class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                    {{ user.email }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <!-- Institution -->
                                <div class="p-3.5 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        <Building1Regular class="w-3.5 h-3.5" />
                                        <span>{{ t('field.institution') }}</span>
                                    </div>
                                    <p class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ user.institution || '—' }}
                                    </p>
                                </div>

                                <!-- Pronoun -->
                                <div class="p-3.5 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        <UserHeartRegular class="w-3.5 h-3.5" />
                                        <span>{{ t('field.pronoun') }}</span>
                                    </div>
                                    <p class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ pronounLabel(user.pronoun) }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <!-- Educational Area -->
                                <div class="p-3.5 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        <Book2Regular class="w-3.5 h-3.5" />
                                        <span>{{ t('field.educational_area') }}</span>
                                    </div>
                                    <p class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ user.educational_area || '—' }}
                                    </p>
                                </div>

                                <!-- Educational Level -->
                                <div class="p-3.5 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        <AwardRegular class="w-3.5 h-3.5" />
                                        <span>{{ t('field.educational_level') }}</span>
                                    </div>
                                    <p class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ user.educational_level || '—' }}
                                    </p>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button
                                    type="button"
                                    @click="startEditing"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-accent-600 dark:text-accent-400 hover:text-accent-700 transition-colors"
                                >
                                    <Edit3Regular class="w-4 h-4" />
                                    <span>{{ t('profile.edit') }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- ── EDIT MODE (Refined Form) ── -->
                        <form v-else @submit.prevent="submitProfile" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <CpInput
                                    id="first_name"
                                    v-model="profileForm.first_name"
                                    :label="t('field.first_name')"
                                    :error="profileForm.errors.first_name"
                                    required
                                    autofocus
                                />
                                <CpInput
                                    id="last_name"
                                    v-model="profileForm.last_name"
                                    :label="t('field.last_name')"
                                    :error="profileForm.errors.last_name"
                                    required
                                />
                            </div>

                            <CpInput
                                id="email"
                                v-model="profileForm.email"
                                type="email"
                                :label="t('field.email')"
                                :error="profileForm.errors.email"
                                required
                            />

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <CpInput
                                    id="institution"
                                    v-model="profileForm.institution"
                                    :label="t('field.institution')"
                                    :error="profileForm.errors.institution"
                                />
                                <CpSelect
                                    id="pronoun"
                                    v-model="profileForm.pronoun"
                                    :label="t('field.pronoun')"
                                    :error="profileForm.errors.pronoun"
                                    :options="pronounOptions"
                                    :placeholder="'—'"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <CpInput
                                    id="educational_area"
                                    v-model="profileForm.educational_area"
                                    :label="t('field.educational_area')"
                                    :error="profileForm.errors.educational_area"
                                />
                                <CpInput
                                    id="educational_level"
                                    v-model="profileForm.educational_level"
                                    :label="t('field.educational_level')"
                                    :error="profileForm.errors.educational_level"
                                />
                            </div>

                            <div class="flex items-center gap-3 pt-3 border-t border-cafe-100 dark:border-surface-dark-3">
                                <CpButton
                                    type="submit"
                                    :loading="profileForm.processing"
                                    :disabled="profileForm.processing"
                                >
                                    {{ t('profile.save_changes') }}
                                </CpButton>
                                <CpButton
                                    type="button"
                                    variant="secondary"
                                    @click="cancelEditing"
                                >
                                    {{ t('profile.cancel_edit') }}
                                </CpButton>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- ── Right Column (2/5): Security & Recovery Vault ── -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- SECTION 1: Two-Factor Authentication (2FA / MFA) -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs space-y-5">
                        <div class="flex items-center justify-between gap-3 border-b border-cafe-100 dark:border-surface-dark-3 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-surface-dark-2 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0">
                                    <ShieldShapeRegular class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ t('profile.two_factor_title') }}
                                    </h2>
                                    <p class="text-[11px] text-cafe-500 dark:text-cafe-400">
                                        {{ t('profile.two_factor_description') }}
                                    </p>
                                </div>
                            </div>

                            <!-- 2FA Status Badge -->
                            <span
                                v-if="isTwoFactorEnabled"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-800/50 shrink-0"
                            >
                                <CheckCircleRegular class="w-3.5 h-3.5" />
                                <span>{{ t('profile.two_factor_active') }}</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-cafe-100 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300 border border-cafe-200/70 dark:border-surface-dark-3 shrink-0"
                            >
                                <span>{{ t('profile.two_factor_inactive') }}</span>
                            </span>
                        </div>

                        <!-- 2FA Details & Action -->
                        <div class="p-4 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40 space-y-3">
                            <div v-if="isTwoFactorEnabled" class="space-y-3">
                                <p v-if="twoFactorConfirmedDate" class="text-xs text-cafe-600 dark:text-cafe-300">
                                    <span class="font-medium text-cafe-800 dark:text-cafe-200">{{ t('profile.two_factor_enabled_on') }}:</span>
                                    {{ twoFactorConfirmedDate }}
                                </p>
                                <p class="text-[11px] text-cafe-500 dark:text-cafe-400 leading-relaxed">
                                    Cada inicio de sesión requerirá el código de tu app autenticadora o un código de recuperación.
                                </p>
                                <div>
                                    <button
                                        type="button"
                                        @click="open2faDisable"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-state-danger hover:underline transition-all"
                                    >
                                        {{ t('profile.two_factor_disable_button') }}
                                    </button>
                                </div>
                            </div>

                            <div v-else class="space-y-3">
                                <p class="text-xs text-cafe-600 dark:text-cafe-300 leading-relaxed">
                                    Protege tu entorno docente contra accesos no autorizados mediante autenticación TOTP estándar.
                                </p>
                                <CpButton
                                    type="button"
                                    @click="open2faSetup"
                                    class="w-full justify-center"
                                >
                                    <QrcodeRegular class="w-4 h-4 mr-1.5" />
                                    <span>{{ t('profile.two_factor_enable_button') }}</span>
                                </CpButton>
                            </div>
                        </div>
                    </section>

                    <!-- SECTION 2: Recovery Codes Vault -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs space-y-4">
                        <div class="flex items-center justify-between gap-3 border-b border-cafe-100 dark:border-surface-dark-3 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                    <SafeShield2Regular class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ t('profile.recovery_codes_title') }}
                                    </h2>
                                    <p class="text-[11px] text-cafe-500 dark:text-cafe-400">
                                        {{ t('profile.recovery_codes_desc') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Available count badge -->
                            <span
                                class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0"
                                :class="recoveryCodesCount <= 2 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200/60' : 'bg-cafe-100 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300 border border-cafe-200/60'"
                            >
                                {{ recoveryCodesCount }} {{ t('profile.codes_available') }}
                            </span>
                        </div>

                        <!-- Low codes warning if <= 2 -->
                        <div
                            v-if="recoveryCodesCount <= 2"
                            class="flex items-start gap-2.5 p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/40 text-xs text-amber-800 dark:text-amber-300"
                        >
                            <AlertCircleRegular class="w-4 h-4 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
                            <p class="leading-relaxed">{{ t('profile.codes_low_warning') }}</p>
                        </div>

                        <!-- Regenerate action button -->
                        <CpButton
                            type="button"
                            variant="secondary"
                            class="w-full justify-center"
                            @click="openRegenerateModal"
                        >
                            <Refresh1Regular class="w-4 h-4 mr-1.5" />
                            <span>{{ t('profile.regenerate_codes') }}</span>
                        </CpButton>
                    </section>

                    <!-- SECTION 3: Password Update -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs space-y-5">
                        <div class="flex items-center gap-2.5 border-b border-cafe-100 dark:border-surface-dark-3 pb-4">
                            <div class="w-8 h-8 rounded-lg bg-cafe-100 dark:bg-surface-dark-2 text-cafe-800 dark:text-cafe-200 flex items-center justify-center shrink-0">
                                <LockRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-cafe-900 dark:text-cafe-100">
                                    {{ t('profile.security') }}
                                </h2>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                    {{ t('profile.security_description') }}
                                </p>
                            </div>
                        </div>

                        <!-- Success status for password -->
                        <div
                            v-if="passwordSaved"
                            class="flex items-center gap-2 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200/80 dark:border-emerald-800/40 text-xs font-semibold text-emerald-800 dark:text-emerald-200"
                        >
                            <CheckCircleRegular class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <span>{{ t('profile.password_updated_success') }}</span>
                        </div>

                        <form @submit.prevent="submitPassword" class="space-y-4">
                            <CpInput
                                id="current_password"
                                v-model="passwordForm.current_password"
                                type="password"
                                :label="t('field.current_password')"
                                :error="passwordForm.errors.current_password"
                                required
                                autocomplete="current-password"
                            />
                            <CpInput
                                id="password"
                                v-model="passwordForm.password"
                                type="password"
                                :label="t('field.new_password')"
                                :error="passwordForm.errors.password"
                                required
                                autocomplete="new-password"
                            />
                            <CpInput
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                :label="t('field.new_password_confirmation')"
                                :error="passwordForm.errors.password_confirmation"
                                required
                                autocomplete="new-password"
                            />

                            <p class="text-[11px] text-cafe-500 dark:text-cafe-400 leading-relaxed">
                                {{ t('profile.password_requirements') }}
                            </p>

                            <CpButton
                                type="submit"
                                :loading="passwordForm.processing"
                                :disabled="passwordForm.processing"
                                class="w-full justify-center"
                            >
                                <Key1Regular class="w-4 h-4 mr-1.5" />
                                <span>{{ t('settings.change_password') }}</span>
                            </CpButton>
                        </form>
                    </section>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- MODAL 1: 2FA SETUP (QR & TOTP VERIFICATION) -->
        <!-- ══════════════════════════════════════════════ -->
        <div
            v-if="show2faSetupModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-cafe-950/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div
                class="w-full max-w-md bg-white dark:bg-surface-dark-1 rounded-2xl shadow-xl border border-cafe-200/80 dark:border-surface-dark-3 p-6 space-y-5 animate-in fade-in zoom-in-95 duration-150 max-h-[90vh] overflow-y-auto"
            >
                <div class="flex items-center justify-between border-b border-cafe-100 dark:border-surface-dark-3 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-surface-dark-2 text-amber-700 dark:text-amber-400 flex items-center justify-center">
                            <QrcodeRegular class="w-4 h-4" />
                        </div>
                        <h3 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                            {{ t('profile.two_factor_modal_title') }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="close2faSetup"
                        class="text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-200 transition-colors"
                    >
                        <CloseCircleRegular class="w-5 h-5" />
                    </button>
                </div>

                <!-- Loading spinner -->
                <div v-if="loadingSetup" class="py-12 text-center text-cafe-500 space-y-2">
                    <Refresh1Regular class="w-6 h-6 animate-spin mx-auto text-accent-600" />
                    <p class="text-xs">Generando clave de cifrado...</p>
                </div>

                <div v-else-if="twoFactorSetupData" class="space-y-4">
                    <!-- Step 1: Scan QR -->
                    <div class="space-y-2.5">
                        <p class="text-xs font-semibold text-cafe-700 dark:text-cafe-300 leading-relaxed">
                            {{ t('profile.two_factor_modal_step1') }}
                        </p>

                        <!-- QR SVG container -->
                        <div class="flex justify-center p-4 bg-white rounded-xl border border-cafe-200/70 shadow-xs">
                            <div class="w-48 h-48 flex items-center justify-center" v-html="twoFactorSetupData.qr_svg" />
                        </div>
                    </div>

                    <!-- Manual Secret Key with Copy Button -->
                    <div class="space-y-1.5">
                        <p class="text-[11px] text-cafe-500 dark:text-cafe-400">
                            {{ t('profile.two_factor_modal_manual_key') }}
                        </p>
                        <div class="flex items-center gap-2">
                            <div class="grow font-mono text-xs font-bold text-cafe-800 dark:text-cafe-200 bg-cafe-100/70 dark:bg-surface-dark-2 px-3 py-2 rounded-xl select-all break-all border border-cafe-200/60 dark:border-surface-dark-3">
                                {{ twoFactorSetupData.secret }}
                            </div>
                            <button
                                type="button"
                                @click="copySecretKey"
                                class="shrink-0 p-2 rounded-xl bg-cafe-100 hover:bg-cafe-200 dark:bg-surface-dark-2 dark:hover:bg-surface-dark-3 text-cafe-700 dark:text-cafe-300 text-xs font-bold flex items-center gap-1 transition-colors"
                                :title="t('profile.copy_key')"
                            >
                                <Copy2Regular class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Verification Code -->
                    <div class="space-y-2 pt-2 border-t border-cafe-100 dark:border-surface-dark-3">
                        <p class="text-xs font-semibold text-cafe-700 dark:text-cafe-300">
                            {{ t('profile.two_factor_modal_step2') }}
                        </p>

                        <CpInput
                            id="two_factor_verification_code"
                            v-model="twoFactorCode"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            placeholder="123456"
                            :error="twoFactorCodeError"
                            class="tracking-widest font-mono text-center text-lg"
                            autofocus
                        />
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-cafe-100 dark:border-surface-dark-3">
                        <CpButton
                            type="button"
                            variant="secondary"
                            @click="close2faSetup"
                        >
                            {{ t('profile.cancel_edit') }}
                        </CpButton>

                        <CpButton
                            type="button"
                            :loading="confirming2fa"
                            :disabled="confirming2fa || !twoFactorCode"
                            @click="confirm2fa"
                        >
                            {{ t('profile.two_factor_modal_confirm_btn') }}
                        </CpButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- MODAL 2: 2FA DISABLE CONFIRMATION -->
        <!-- ══════════════════════════════════════════════ -->
        <div
            v-if="show2faDisableModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-cafe-950/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div
                class="w-full max-w-md bg-white dark:bg-surface-dark-1 rounded-2xl shadow-xl border border-cafe-200/80 dark:border-surface-dark-3 p-6 space-y-5 animate-in fade-in zoom-in-95 duration-150"
            >
                <div class="flex items-center justify-between border-b border-cafe-100 dark:border-surface-dark-3 pb-3">
                    <h3 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                        {{ t('profile.two_factor_disable_title') }}
                    </h3>
                    <button
                        type="button"
                        @click="close2faDisable"
                        class="text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-200 transition-colors"
                    >
                        <CloseCircleRegular class="w-5 h-5" />
                    </button>
                </div>

                <p class="text-xs text-cafe-600 dark:text-cafe-300 leading-relaxed">
                    {{ t('profile.two_factor_disable_warning') }}
                </p>

                <div>
                    <CpInput
                        id="disable_password"
                        v-model="disablePassword"
                        type="password"
                        :label="t('field.current_password')"
                        :error="disablePasswordError"
                        required
                        autofocus
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-cafe-100 dark:border-surface-dark-3">
                    <CpButton
                        type="button"
                        variant="secondary"
                        @click="close2faDisable"
                    >
                        {{ t('profile.cancel_edit') }}
                    </CpButton>

                    <CpButton
                        type="button"
                        variant="danger"
                        :loading="disabling2fa"
                        :disabled="disabling2fa || !disablePassword"
                        @click="confirmDisable2fa"
                    >
                        {{ t('profile.two_factor_disable_button') }}
                    </CpButton>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- MODAL 3: REGENERATE RECOVERY CODES CONFIRM -->
        <!-- ══════════════════════════════════════════════ -->
        <div
            v-if="showRegenerateModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-cafe-950/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div
                class="w-full max-w-md bg-white dark:bg-surface-dark-1 rounded-2xl shadow-xl border border-cafe-200/80 dark:border-surface-dark-3 p-6 space-y-5 animate-in fade-in zoom-in-95 duration-150"
            >
                <div class="flex items-center justify-between border-b border-cafe-100 dark:border-surface-dark-3 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-surface-dark-2 text-amber-700 dark:text-amber-400 flex items-center justify-center">
                            <SafeShield2Regular class="w-4 h-4" />
                        </div>
                        <h3 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                            {{ t('profile.regenerate_confirm_title') }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="closeRegenerateModal"
                        class="text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-200 transition-colors"
                    >
                        <CloseCircleRegular class="w-5 h-5" />
                    </button>
                </div>

                <p class="text-xs text-cafe-600 dark:text-cafe-300 leading-relaxed">
                    {{ t('profile.regenerate_confirm_desc') }}
                </p>

                <div>
                    <CpInput
                        id="regenerate_password"
                        v-model="regeneratePassword"
                        type="password"
                        :label="t('field.current_password')"
                        :error="regeneratePasswordError"
                        required
                        autofocus
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-cafe-100 dark:border-surface-dark-3">
                    <CpButton
                        type="button"
                        variant="secondary"
                        @click="closeRegenerateModal"
                    >
                        {{ t('profile.cancel_edit') }}
                    </CpButton>

                    <CpButton
                        type="button"
                        :loading="regeneratingCodes"
                        :disabled="regeneratingCodes || !regeneratePassword"
                        @click="confirmRegenerateCodes"
                    >
                        {{ t('profile.regenerate_codes') }}
                    </CpButton>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- MODAL 4: NEW RECOVERY CODES VAULT DISPLAY -->
        <!-- ══════════════════════════════════════════════ -->
        <div
            v-if="showVaultDisplayModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-cafe-950/60 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
        >
            <div
                class="w-full max-w-md bg-white dark:bg-surface-dark-1 rounded-2xl shadow-xl border border-cafe-200/80 dark:border-surface-dark-3 p-6 space-y-5 animate-in fade-in zoom-in-95 duration-150"
            >
                <div class="flex items-center justify-between border-b border-cafe-100 dark:border-surface-dark-3 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 flex items-center justify-center">
                            <SafeShield2Regular class="w-4 h-4" />
                        </div>
                        <h3 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                            {{ t('profile.recovery_vault_modal_title') }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="closeVaultDisplay"
                        class="text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-200 transition-colors"
                    >
                        <CloseCircleRegular class="w-5 h-5" />
                    </button>
                </div>

                <!-- Warning note -->
                <div class="flex items-start gap-2.5 p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-800/40 text-xs text-amber-800 dark:text-amber-300">
                    <AlertCircleRegular class="w-4 h-4 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
                    <p class="leading-relaxed">{{ t('profile.recovery_vault_modal_warning') }}</p>
                </div>

                <!-- 2-column Monospace Grid -->
                <div class="grid grid-cols-2 gap-2.5 p-3.5 bg-cafe-50/70 dark:bg-surface-dark-2/60 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3">
                    <div
                        v-for="(code, idx) in newRecoveryCodes"
                        :key="idx"
                        class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-surface-dark-1 rounded-lg border border-cafe-200/60 dark:border-surface-dark-3 shadow-2xs"
                    >
                        <span class="text-[10px] font-bold text-cafe-400 font-mono">{{ idx + 1 }}.</span>
                        <span class="font-mono text-xs font-bold text-cafe-900 dark:text-cafe-100 tracking-wider select-all">{{ code }}</span>
                    </div>
                </div>

                <!-- Action buttons: Copy all & Download txt -->
                <div class="grid grid-cols-2 gap-2.5 pt-1">
                    <button
                        type="button"
                        @click="copyAllCodes"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-cafe-100 dark:bg-surface-dark-2 text-xs font-bold text-cafe-800 dark:text-cafe-200 hover:bg-cafe-200 transition-colors"
                    >
                        <Copy2Regular class="w-4 h-4" />
                        <span>{{ t('profile.copy_all_codes') }}</span>
                    </button>

                    <button
                        type="button"
                        @click="downloadCodesFile"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-cafe-100 dark:bg-surface-dark-2 text-xs font-bold text-cafe-800 dark:text-cafe-200 hover:bg-cafe-200 transition-colors"
                    >
                        <Download2Regular class="w-4 h-4" />
                        <span>{{ t('profile.download_txt') }}</span>
                    </button>
                </div>

                <!-- Confirm Acknowledgment -->
                <div class="pt-3 border-t border-cafe-100 dark:border-surface-dark-3">
                    <CpButton
                        type="button"
                        class="w-full justify-center"
                        @click="closeVaultDisplay"
                    >
                        {{ t('profile.i_saved_codes') }}
                    </CpButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
