<script setup>
/**
 * Profile Index — /perfil
 * READ MODE by default, two-column grid.
 * Left: Personal info (read or edit). Right: Security + Recovery.
 * "Edit Profile" button toggles to editable form.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpInput from '@/Components/CpInput.vue';
import CpSelect from '@/Components/CpSelect.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const { t } = useTranslations();
const page = usePage();

const props = defineProps({
    user: Object,
});

const editing = ref(false);

// Profile form
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
        },
    });
}

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submitPassword() {
    passwordForm.put(route('profile.update-password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

const pronounOptions = [
    { value: 'él', label: t('pronoun.el') },
    { value: 'ella', label: t('pronoun.ella') },
    { value: 'elle', label: t('pronoun.elle') },
];

const profileSaved = computed(() => page.props.flash?.status === 'profile-updated');
const passwordSaved = computed(() => page.props.flash?.status === 'password-updated');

// Display helpers for read mode
function pronounLabel(val) {
    const opt = pronounOptions.find(o => o.value === val);
    return opt ? opt.label : val || '—';
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('profile.title')" />

        <div>
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="font-serif mb-1">{{ t('profile.title') }}</h1>
                    <p class="text-sm text-cafe-500 dark:text-cafe-400">
                        {{ t('profile.subtitle') }}
                    </p>
                </div>
                <CpButton
                    v-if="!editing"
                    type="button"
                    variant="secondary"
                    @click="startEditing"
                >
                    {{ t('profile.edit') }}
                </CpButton>
            </div>

            <div v-if="profileSaved && !editing" class="mb-6 text-sm text-state-success">
                {{ t('settings.saved') }}
            </div>

            <!-- Two-column grid: 60/40 -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

                <!-- ═══ LEFT COLUMN (3/5) — Personal Information ═══ -->
                <div class="lg:col-span-3">
                    <section class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                        <h2 class="text-base font-semibold text-cafe-800 dark:text-cafe-100 mb-1">
                            {{ t('profile.personal_info') }}
                        </h2>
                        <p class="text-sm text-cafe-500 dark:text-cafe-400 mb-5">
                            {{ t('profile.personal_info_description') }}
                        </p>

                        <!-- ── READ MODE ── -->
                        <div v-if="!editing" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        {{ t('field.first_name') }}
                                    </dt>
                                    <dd class="text-sm text-cafe-800 dark:text-cafe-100">
                                        {{ user.first_name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        {{ t('field.last_name') }}
                                    </dt>
                                    <dd class="text-sm text-cafe-800 dark:text-cafe-100">
                                        {{ user.last_name }}
                                    </dd>
                                </div>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                    {{ t('field.email') }}
                                </dt>
                                <dd class="text-sm text-cafe-800 dark:text-cafe-100">
                                    {{ user.email }}
                                </dd>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        {{ t('field.institution') }}
                                    </dt>
                                    <dd class="text-sm text-cafe-800 dark:text-cafe-100">
                                        {{ user.institution || '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        {{ t('field.pronoun') }}
                                    </dt>
                                    <dd class="text-sm text-cafe-800 dark:text-cafe-100">
                                        {{ pronounLabel(user.pronoun) }}
                                    </dd>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        {{ t('field.educational_area') }}
                                    </dt>
                                    <dd class="text-sm text-cafe-800 dark:text-cafe-100">
                                        {{ user.educational_area || '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wide mb-1">
                                        {{ t('field.educational_level') }}
                                    </dt>
                                    <dd class="text-sm text-cafe-800 dark:text-cafe-100">
                                        {{ user.educational_level || '—' }}
                                    </dd>
                                </div>
                            </div>
                        </div>

                        <!-- ── EDIT MODE ── -->
                        <form v-else @submit.prevent="submitProfile" class="space-y-5">
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

                            <div class="flex items-center gap-3">
                                <CpButton
                                    :loading="profileForm.processing"
                                    :disabled="profileForm.processing"
                                >
                                    {{ t('settings.save') }}
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

                <!-- ═══ RIGHT COLUMN (2/5) — Security + Recovery ═══ -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- ── Password ── -->
                    <section class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                        <h2 class="text-base font-semibold text-cafe-800 dark:text-cafe-100 mb-1">
                            {{ t('profile.security') }}
                        </h2>
                        <p class="text-sm text-cafe-500 dark:text-cafe-400 mb-5">
                            {{ t('profile.security_description') }}
                        </p>

                        <div v-if="passwordSaved" class="mb-4 text-sm text-state-success">
                            {{ t('settings.saved') }}
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
                            <CpButton
                                :loading="passwordForm.processing"
                                :disabled="passwordForm.processing"
                            >
                                {{ t('settings.change_password') }}
                            </CpButton>
                        </form>
                    </section>

                    <!-- ── Recovery Codes ── -->
                    <section class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-6">
                        <h2 class="text-base font-semibold text-cafe-800 dark:text-cafe-100 mb-1">
                            {{ t('settings.recovery_codes') }}
                        </h2>
                        <p class="text-sm text-cafe-500 dark:text-cafe-400 mb-4">
                            {{ t('settings.recovery_codes_description') }}
                        </p>
                        <Link :href="route('recovery-codes.show')">
                            <CpButton variant="secondary" type="button">
                                {{ t('settings.manage_codes') }}
                            </CpButton>
                        </Link>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
