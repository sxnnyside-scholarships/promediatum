<script setup lang="ts">
/**
 * Profile Index — /perfil
 * Unified with the Café Pedagógico Design System:
 * - Educator Identity Card with initials pseudo-avatar, adscripción, and role badge
 * - Professional Information (Read mode with structured cards, Edit mode with refined inputs)
 * - Security & Recovery Vault (Password change with inline strength guidance, Recovery Codes status)
 * - Pedagogical contextual guidance on how profile data enriches academic export documents
 */

import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AwardRegular,
    Book2Regular,
    Building1Regular,
    BulbRegular,
    CheckCircleRegular,
    CloseCircleRegular,
    Edit3Regular,
    Key1Regular,
    LockRegular,
    MailRegular,
    RightSmallRegular,
    SafeShield2Regular,
    User1Regular,
    UserHeartRegular,
    UserSecurityRegular,
} from '@mingcute/vue/core-regular';
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
}

const props = defineProps<{
    user: UserProfileData;
}>();

const editing = ref(false);

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
                    <!-- SECTION: Password Update -->
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

                    <!-- SECTION: Recovery Codes Vault -->
                    <section class="rounded-2xl border border-cafe-200/80 dark:border-surface-dark-3 bg-white/90 dark:bg-surface-dark-1 p-6 shadow-xs space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                    <SafeShield2Regular class="w-4 h-4" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ t('settings.recovery_codes') }}
                                    </h2>
                                    <p class="text-[11px] text-cafe-500 dark:text-cafe-400">
                                        {{ t('settings.recovery_codes_description') }}
                                    </p>
                                </div>
                            </div>

                            <span
                                v-if="user.unused_recovery_codes_count !== undefined"
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cafe-100 dark:bg-surface-dark-3 text-cafe-700 dark:text-cafe-300 shrink-0"
                            >
                                {{ user.unused_recovery_codes_count }} {{ t('profile.codes_available') }}
                            </span>
                        </div>

                        <Link :href="route('recovery-codes.show')" class="block">
                            <div class="flex items-center justify-between p-3.5 rounded-xl border border-cafe-200/70 dark:border-surface-dark-3 bg-cafe-50/40 dark:bg-surface-dark-2/40 hover:bg-cafe-100/60 dark:hover:bg-surface-dark-2 transition-colors group">
                                <div class="flex items-center gap-2.5">
                                    <UserSecurityRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                                    <span class="text-xs font-bold text-cafe-800 dark:text-cafe-200 group-hover:text-accent-600 dark:group-hover:text-accent-400 transition-colors">
                                        {{ t('profile.manage_vault') }}
                                    </span>
                                </div>
                                <RightSmallRegular class="w-4 h-4 text-cafe-400 group-hover:translate-x-0.5 transition-transform" />
                            </div>
                        </Link>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
