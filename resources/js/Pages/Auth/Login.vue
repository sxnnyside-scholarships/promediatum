<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import CpInput from '@/Components/CpInput.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, useForm } from '@inertiajs/vue3';

const { t } = useTranslations();

const props = defineProps({
    status: String,
    userExists: {
        type: Boolean,
        default: true,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.login')" />

        <div>
            <!-- Page title -->
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-cafe-900 dark:text-cafe-50 font-serif">
                    {{ t('login.title') }}
                </h1>
                <p class="mt-1 text-sm text-cafe-500 dark:text-cafe-400">
                    {{ t('login.subtitle') }}
                </p>
            </div>

            <!-- No account exists message -->
            <div
                v-if="!userExists"
                class="mb-6 bg-accent-50 dark:bg-surface-dark-2 border border-accent-200 dark:border-accent-800 rounded-subtle px-4 py-3"
            >
                <p class="text-sm text-cafe-700 dark:text-cafe-200">
                    {{ t('login.no_account_exists') }}
                    <Link
                        :href="route('register')"
                        class="text-accent-500 dark:text-accent-400 underline underline-offset-2 font-medium ml-1"
                    >
                        {{ t('login.create_account') }}
                    </Link>
                </p>
            </div>

            <!-- Status message (e.g., after password reset) -->
            <div
                v-if="status"
                class="mb-6 text-sm text-state-success bg-cafe-100 dark:bg-surface-dark-2 px-4 py-3 rounded-subtle"
            >
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <CpInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    :label="t('field.email')"
                    :error="form.errors.email"
                    required
                    autofocus
                    autocomplete="email"
                />

                <CpInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    :label="t('field.password')"
                    :error="form.errors.password"
                    required
                    autocomplete="current-password"
                />

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded-subtle border-cafe-300 text-accent-400
                                   focus:ring-accent-400
                                   dark:border-cafe-600 dark:bg-surface-dark-2"
                        />
                        <span class="text-sm text-cafe-600 dark:text-cafe-400">
                            {{ t('auth.remember_me') }}
                        </span>
                    </label>

                    <Link
                        v-if="userExists"
                        :href="route('password.request')"
                        class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                    >
                        {{ t('auth.forgot_password') }}
                    </Link>
                </div>

                <CpButton
                    :loading="form.processing"
                    :disabled="form.processing"
                    class="w-full justify-center"
                >
                    {{ t('login.submit') }}
                </CpButton>
            </form>
        </div>
    </GuestLayout>
</template>
