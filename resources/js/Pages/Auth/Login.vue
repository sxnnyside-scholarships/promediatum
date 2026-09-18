<script setup lang="ts">
/**
 * Login.vue — Café Pedagógico Professional Auth Gateway
 *
 * Provides real-time field prevalidation (regex, empty states, length),
 * visual indicators, password visibility toggle, unified mode switching
 * (Login, Register for initial setup, and Recovery Code), and strict single-user enforcement.
 */

import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertRegular,
    CheckCircleRegular,
    CheckRegular,
    EyeCloseRegular,
    EyeRegular,
    LockRegular,
    MailRegular,
} from '@mingcute/vue/core-regular';
import { computed, reactive, ref, watch } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations';
import GuestLayout from '@/Layouts/GuestLayout.vue';

interface Props {
    status?: string | null;
    userExists: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    status: null,
    userExists: true,
});

const { t, locale } = useTranslations();

// Active mode: 'login' | 'register' | 'recovery'
const mode = ref<'login' | 'register' | 'recovery'>(props.userExists ? 'login' : 'register');

// Password visibility toggles
const showPassword = ref(false);
const showPasswordConfirm = ref(false);

// Real-time prevalidation Regexes
const EMAIL_REGEX = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

// ── Login Form State ──
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const loginTouched = reactive({
    email: false,
    password: false,
});

const isEmailValid = computed(() => EMAIL_REGEX.test(loginForm.email.trim()));
const isEmailEmpty = computed(() => loginForm.email.trim().length === 0);
const isPasswordEmpty = computed(() => loginForm.password.length === 0);
const isPasswordLongEnough = computed(() => loginForm.password.length >= 8);

function handleLoginSubmit() {
    loginTouched.email = true;
    loginTouched.password = true;

    if (isEmailEmpty.value || !isEmailValid.value || isPasswordEmpty.value) {
        return;
    }

    loginForm.post(route('login'), {
        onFinish: () => loginForm.reset('password'),
    });
}

// ── Register Form State (Initial Single-User Setup) ──
const registerForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    institution: '',
    pronoun: 'él',
    educational_area: '',
    educational_level: 'Secundaria',
});

const registerTouched = reactive({
    first_name: false,
    last_name: false,
    email: false,
    password: false,
    password_confirmation: false,
});

const isRegEmailValid = computed(() => EMAIL_REGEX.test(registerForm.email.trim()));
const isRegPasswordMatch = computed(
    () =>
        registerForm.password.length > 0 &&
        registerForm.password === registerForm.password_confirmation,
);
const isRegPasswordLength = computed(() => registerForm.password.length >= 8);

function handleRegisterSubmit() {
    registerTouched.first_name = true;
    registerTouched.last_name = true;
    registerTouched.email = true;
    registerTouched.password = true;
    registerTouched.password_confirmation = true;

    if (
        !registerForm.first_name.trim() ||
        !registerForm.last_name.trim() ||
        !isRegEmailValid.value ||
        !isRegPasswordLength.value ||
        !isRegPasswordMatch.value
    ) {
        return;
    }

    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
}

// ── Recovery Code Form State ──
const recoveryForm = useForm({
    email: '',
    recovery_code: '',
});

const recoveryTouched = reactive({
    email: false,
    recovery_code: false,
});

const isRecEmailValid = computed(() => EMAIL_REGEX.test(recoveryForm.email.trim()));
const isRecCodeValid = computed(() => recoveryForm.recovery_code.trim().length >= 8);

function handleRecoverySubmit() {
    recoveryTouched.email = true;
    recoveryTouched.recovery_code = true;

    if (!isRecEmailValid.value || !isRecCodeValid.value) {
        return;
    }

    recoveryForm.post(route('password.verify'), {
        onFinish: () => recoveryForm.reset('recovery_code'),
    });
}
</script>

<template>
    <GuestLayout>
        <Head :title="mode === 'register' ? t('auth.academic_register') : mode === 'recovery' ? t('auth.academic_recovery') : t('auth.academic_login')" />

        <div class="w-full flex flex-col justify-center">
            <!-- Header: Academic Tone, 0 Eyebrows -->
            <div class="mb-3.5">
                <h2 class="text-xl lg:text-2xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                    <span v-if="mode === 'register'">{{ t('auth.academic_register') }}</span>
                    <span v-else-if="mode === 'recovery'">{{ t('auth.academic_recovery') }}</span>
                    <span v-else>{{ t('auth.academic_login') }}</span>
                </h2>

                <p class="mt-1 text-xs text-cafe-600 dark:text-cafe-400 leading-relaxed">
                    <span v-if="mode === 'register'">{{ t('auth.academic_register_subtitle') }}</span>
                    <span v-else-if="mode === 'recovery'">{{ t('auth.academic_recovery_subtitle') }}</span>
                    <span v-else>{{ t('auth.academic_login_subtitle') }}</span>
                </p>
            </div>

            <!-- Status Notification -->
            <div
                v-if="status"
                class="mb-4 px-3.5 py-2.5 rounded-lg text-xs font-medium bg-state-success/15 border border-state-success/30 text-state-success flex items-center gap-2"
            >
                <CheckCircleRegular class="w-4 h-4 shrink-0 text-state-success" />
                <span>{{ status }}</span>
            </div>

            <!-- Notice if no user account exists yet -->
            <div
                v-if="!userExists && mode === 'login'"
                class="mb-4 p-3.5 rounded-xl bg-accent-50/80 dark:bg-surface-dark-2 border border-accent-200 dark:border-accent-800/80 text-xs text-cafe-800 dark:text-cafe-200"
            >
                <p class="font-semibold mb-1 text-accent-700 dark:text-accent-300">
                    {{ t('auth.no_profile_title') }}
                </p>
                <p class="text-cafe-600 dark:text-cafe-400 mb-2.5 leading-relaxed">
                    {{ t('auth.no_profile_desc') }}
                </p>
                <button
                    type="button"
                    @click="mode = 'register'"
                    class="w-full py-2 px-3 rounded-lg bg-accent-500 text-white font-medium hover:bg-accent-600 transition-colors text-center text-xs shadow-sm"
                >
                    {{ t('auth.create_profile') }}
                </button>
            </div>

            <!-- ══════════════════════════════════════════════════════ -->
            <!-- MODE: LOGIN                                          -->
            <!-- ══════════════════════════════════════════════════════ -->
            <form v-if="mode === 'login'" @submit.prevent="handleLoginSubmit" class="space-y-4">
                <!-- Email with Regex & Empty Prevalidation -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="login-email" class="cp-label !mb-0 text-xs font-medium">
                            {{ t('field.email') }}
                        </label>
                        <span
                            v-if="loginTouched.email && !isEmailEmpty && isEmailValid"
                            class="text-[11px] font-medium text-state-success flex items-center gap-1"
                        >
                            <CheckRegular class="w-3.5 h-3.5" />
                            {{ t('auth.valid_field') }}
                        </span>
                    </div>

                    <div class="relative">
                        <input
                            id="login-email"
                            v-model="loginForm.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="docente@escuela.edu"
                            class="cp-input text-sm py-2.5 pr-10"
                            :class="{
                                '!border-state-danger ring-1 ring-state-danger/30': (loginTouched.email && (isEmailEmpty || !isEmailValid)) || loginForm.errors.email,
                                '!border-state-success': loginTouched.email && !isEmailEmpty && isEmailValid && !loginForm.errors.email
                            }"
                            @blur="loginTouched.email = true"
                        />
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-cafe-400">
                            <MailRegular class="w-4 h-4" />
                        </div>
                    </div>

                    <p v-if="loginTouched.email && isEmailEmpty" class="text-[11px] text-state-danger mt-1.5">
                        {{ t('auth.email_required') }}
                    </p>
                    <p v-else-if="loginTouched.email && !isEmailValid" class="text-[11px] text-state-danger mt-1.5">
                        {{ t('auth.email_invalid') }}
                    </p>
                    <p v-else-if="loginForm.errors.email" class="text-[11px] text-state-danger mt-1.5">
                        {{ loginForm.errors.email }}
                    </p>
                </div>

                <!-- Password with show/hide & empty prevalidation -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="login-password" class="cp-label !mb-0 text-xs font-medium">
                            {{ t('field.password') }}
                        </label>
                        <button
                            v-if="userExists"
                            type="button"
                            @click="mode = 'recovery'"
                            class="text-xs text-accent-500 hover:text-accent-600 dark:text-accent-400 hover:underline transition-colors"
                        >
                            {{ t('auth.forgot_password') }}
                        </button>
                    </div>

                    <div class="relative">
                        <input
                            id="login-password"
                            v-model="loginForm.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="cp-input text-sm py-2.5 pr-11"
                            :class="{
                                '!border-state-danger ring-1 ring-state-danger/30': (loginTouched.password && isPasswordEmpty) || loginForm.errors.password
                            }"
                            @blur="loginTouched.password = true"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-200 transition-colors"
                            tabindex="-1"
                            :title="showPassword ? 'Ocultar' : 'Mostrar'"
                        >
                            <component :is="showPassword ? EyeCloseRegular : EyeRegular" class="w-4 h-4" />
                        </button>
                    </div>

                    <p v-if="loginTouched.password && isPasswordEmpty" class="text-[11px] text-state-danger mt-1.5">
                        {{ t('auth.password_required') }}
                    </p>
                    <p v-else-if="loginForm.errors.password" class="text-[11px] text-state-danger mt-1.5">
                        {{ loginForm.errors.password }}
                    </p>
                </div>

                <!-- Remember me -->
                <div class="flex items-center justify-between pt-0.5">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input
                            v-model="loginForm.remember"
                            type="checkbox"
                            class="rounded border-cafe-300 text-accent-500 focus:ring-accent-400 dark:border-cafe-600 dark:bg-surface-dark-2 h-4 w-4"
                        />
                        <span class="text-xs text-cafe-600 dark:text-cafe-300">
                            {{ t('auth.remember_me') }}
                        </span>
                    </label>
                </div>

                <!-- Submit Button -->
                <CpButton
                    type="submit"
                    :loading="loginForm.processing"
                    :disabled="loginForm.processing || (loginTouched.email && !isEmailValid)"
                    class="w-full justify-center text-sm py-3 mt-1 font-semibold shadow-sm rounded-xl"
                >
                    {{ t('auth.enter_workspace') }}
                </CpButton>

                <!-- Initial register toggle (if single account not created yet) -->
                <div v-if="!userExists" class="text-center pt-2">
                    <button
                        type="button"
                        @click="mode = 'register'"
                        class="text-xs text-cafe-500 hover:text-cafe-800 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2"
                    >
                        {{ t('auth.first_time_question') }}
                    </button>
                </div>
            </form>

            <!-- ══════════════════════════════════════════════════════ -->
            <!-- MODE: REGISTER (Single-User Enrollment)               -->
            <!-- ══════════════════════════════════════════════════════ -->
            <form v-else-if="mode === 'register'" @submit.prevent="handleRegisterSubmit" class="space-y-3">
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label for="reg-first-name" class="cp-label text-xs font-medium">
                            {{ t('field.first_name') }} *
                        </label>
                        <input
                            id="reg-first-name"
                            v-model="registerForm.first_name"
                            type="text"
                            required
                            placeholder="María"
                            class="cp-input text-xs"
                            :class="{ '!border-state-danger': registerTouched.first_name && !registerForm.first_name.trim() }"
                            @blur="registerTouched.first_name = true"
                        />
                    </div>
                    <div>
                        <label for="reg-last-name" class="cp-label text-xs font-medium">
                            {{ t('field.last_name') }} *
                        </label>
                        <input
                            id="reg-last-name"
                            v-model="registerForm.last_name"
                            type="text"
                            required
                            placeholder="González"
                            class="cp-input text-xs"
                            :class="{ '!border-state-danger': registerTouched.last_name && !registerForm.last_name.trim() }"
                            @blur="registerTouched.last_name = true"
                        />
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="reg-email" class="cp-label text-xs font-medium">
                        {{ t('field.email') }} *
                    </label>
                    <div class="relative">
                        <input
                            id="reg-email"
                            v-model="registerForm.email"
                            type="email"
                            required
                            placeholder="docente@escuela.edu"
                            class="cp-input text-xs pr-9"
                            :class="{
                                '!border-state-danger': registerTouched.email && (!registerForm.email.trim() || !isRegEmailValid),
                                '!border-state-success': registerTouched.email && isRegEmailValid
                            }"
                            @blur="registerTouched.email = true"
                        />
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-cafe-400">
                            <MailRegular class="w-4 h-4" />
                        </div>
                    </div>
                    <p v-if="registerTouched.email && !isRegEmailValid" class="text-[11px] text-state-danger mt-1">
                        {{ t('auth.email_invalid') }}
                    </p>
                </div>

                <!-- Institution (Optional) -->
                <div>
                    <label for="reg-inst" class="cp-label text-xs font-medium">
                        {{ t('field.institution') }}
                    </label>
                    <input
                        id="reg-inst"
                        v-model="registerForm.institution"
                        type="text"
                        placeholder="Escuela Normal Superior"
                        class="cp-input text-xs"
                    />
                </div>

                <!-- Pronoun -->
                <div>
                    <label for="reg-pronoun" class="cp-label text-xs font-medium">
                        {{ t('field.pronoun') }}
                    </label>
                    <select id="reg-pronoun" v-model="registerForm.pronoun" class="cp-input text-xs">
                        <option value="él">{{ t('pronoun.el') }}</option>
                        <option value="ella">{{ t('pronoun.ella') }}</option>
                        <option value="elle">{{ t('pronoun.elle') }}</option>
                    </select>
                </div>

                <!-- Password -->
                <div>
                    <label for="reg-password" class="cp-label text-xs font-medium">
                        {{ t('field.password') }} *
                    </label>
                    <div class="relative">
                        <input
                            id="reg-password"
                            v-model="registerForm.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            placeholder="••••••••"
                            class="cp-input text-xs pr-10"
                            :class="{
                                '!border-state-danger': registerTouched.password && (!registerForm.password || !isRegPasswordLength),
                                '!border-state-success': isRegPasswordLength
                            }"
                            @blur="registerTouched.password = true"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-cafe-400 hover:text-cafe-600 dark:hover:text-cafe-200 transition-colors"
                            tabindex="-1"
                        >
                            <component :is="showPassword ? EyeCloseRegular : EyeRegular" class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Password Confirmation -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="reg-pass-confirm" class="cp-label !mb-0 text-xs font-medium">
                            {{ t('field.password_confirmation') }} *
                        </label>
                        <span v-if="registerForm.password_confirmation && isRegPasswordMatch" class="text-[10px] text-state-success font-medium flex items-center gap-1">
                            <CheckRegular class="w-3.5 h-3.5" />
                            {{ t('auth.match_passwords') }}
                        </span>
                    </div>
                    <input
                        id="reg-pass-confirm"
                        v-model="registerForm.password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        placeholder="••••••••"
                        class="cp-input text-xs"
                        :class="{
                            '!border-state-danger': registerTouched.password_confirmation && !isRegPasswordMatch
                        }"
                        @blur="registerTouched.password_confirmation = true"
                    />
                    <p v-if="registerTouched.password_confirmation && !isRegPasswordMatch" class="text-[11px] text-state-danger mt-1">
                        {{ t('auth.password_mismatch') }}
                    </p>
                </div>

                <CpButton
                    type="submit"
                    :loading="registerForm.processing"
                    class="w-full justify-center text-xs py-2.5 mt-2 font-semibold shadow-sm"
                >
                    {{ t('auth.create_profile') }}
                </CpButton>

                <div v-if="userExists" class="text-center pt-2">
                    <button
                        type="button"
                        @click="mode = 'login'"
                        class="text-xs text-cafe-500 hover:text-cafe-800 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2"
                    >
                        {{ t('auth.back_to_login') }}
                    </button>
                </div>
            </form>

            <!-- ══════════════════════════════════════════════════════ -->
            <!-- MODE: RECOVERY CODE                                  -->
            <!-- ══════════════════════════════════════════════════════ -->
            <form v-else-if="mode === 'recovery'" @submit.prevent="handleRecoverySubmit" class="space-y-3.5">
                <div>
                    <label for="rec-email" class="cp-label text-xs font-medium">
                        {{ t('field.email') }}
                    </label>
                    <input
                        id="rec-email"
                        v-model="recoveryForm.email"
                        type="email"
                        required
                        placeholder="docente@escuela.edu"
                        class="cp-input text-xs"
                        :class="{ '!border-state-danger': recoveryTouched.email && !isRecEmailValid }"
                        @blur="recoveryTouched.email = true"
                    />
                </div>

                <div>
                    <label for="rec-code" class="cp-label text-xs font-medium">
                        {{ t('field.recovery_code') }}
                    </label>
                    <input
                        id="rec-code"
                        v-model="recoveryForm.recovery_code"
                        type="text"
                        required
                        placeholder="XXXX-XXXX-XXXX"
                        class="cp-input text-xs font-mono tracking-widest uppercase"
                        :class="{ '!border-state-danger': recoveryTouched.recovery_code && !isRecCodeValid }"
                        @blur="recoveryTouched.recovery_code = true"
                    />
                    <p class="text-[11px] text-cafe-500 dark:text-cafe-400 mt-1 leading-relaxed">
                        {{ t('auth.recovery_key_hint') }}
                    </p>
                </div>

                <CpButton
                    type="submit"
                    :loading="recoveryForm.processing"
                    class="w-full justify-center text-xs py-2.5 mt-1 font-semibold shadow-sm"
                >
                    {{ t('auth.verify_reset') }}
                </CpButton>

                <div class="text-center pt-2">
                    <button
                        type="button"
                        @click="mode = 'login'"
                        class="text-xs text-cafe-500 hover:text-cafe-800 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2"
                    >
                        {{ t('auth.back_to_login') }}
                    </button>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
