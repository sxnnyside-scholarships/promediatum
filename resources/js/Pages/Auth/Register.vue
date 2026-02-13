<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import CpInput from '@/Components/CpInput.vue';
import CpSelect from '@/Components/CpSelect.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, useForm } from '@inertiajs/vue3';

const { t } = useTranslations();

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    institution: '',
    pronoun: '',
    educational_area: '',
    educational_level: '',
});

const pronounOptions = [
    { value: 'él', label: t('pronoun.el') },
    { value: 'ella', label: t('pronoun.ella') },
    { value: 'elle', label: t('pronoun.elle') },
];

function submit() {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.register')" />

        <div>
            <!-- Page title -->
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-cafe-900 dark:text-cafe-50 font-serif">
                    {{ t('register.title') }}
                </h1>
                <p class="mt-1 text-sm text-cafe-500 dark:text-cafe-400">
                    {{ t('register.subtitle') }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Required Fields Section -->
                <fieldset>
                    <legend class="text-sm font-medium text-cafe-600 dark:text-cafe-300 mb-4">
                        {{ t('register.required_fields') }}
                    </legend>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <CpInput
                                id="first_name"
                                v-model="form.first_name"
                                :label="t('field.first_name')"
                                :error="form.errors.first_name"
                                required
                                autofocus
                                autocomplete="given-name"
                            />
                            <CpInput
                                id="last_name"
                                v-model="form.last_name"
                                :label="t('field.last_name')"
                                :error="form.errors.last_name"
                                required
                                autocomplete="family-name"
                            />
                        </div>

                        <CpInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            :label="t('field.email')"
                            :error="form.errors.email"
                            required
                            autocomplete="email"
                        />

                        <CpInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            :label="t('field.password')"
                            :error="form.errors.password"
                            required
                            autocomplete="new-password"
                        />

                        <CpInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            :label="t('field.password_confirmation')"
                            :error="form.errors.password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                    </div>
                </fieldset>

                <!-- Separator -->
                <div class="cp-separator"></div>

                <!-- Optional Fields Section -->
                <fieldset>
                    <legend class="text-sm font-medium text-cafe-600 dark:text-cafe-300 mb-4">
                        {{ t('register.optional_fields') }}
                    </legend>

                    <div class="space-y-4">
                        <CpInput
                            id="institution"
                            v-model="form.institution"
                            :label="t('field.institution')"
                            :error="form.errors.institution"
                            autocomplete="organization"
                        />

                        <CpSelect
                            id="pronoun"
                            v-model="form.pronoun"
                            :label="t('field.pronoun')"
                            :error="form.errors.pronoun"
                            :options="pronounOptions"
                            :placeholder="'—'"
                        />

                        <CpInput
                            id="educational_area"
                            v-model="form.educational_area"
                            :label="t('field.educational_area')"
                            :error="form.errors.educational_area"
                        />

                        <CpInput
                            id="educational_level"
                            v-model="form.educational_level"
                            :label="t('field.educational_level')"
                            :error="form.errors.educational_level"
                        />
                    </div>
                </fieldset>

                <CpButton
                    :loading="form.processing"
                    :disabled="form.processing"
                    class="w-full justify-center"
                >
                    {{ t('register.submit') }}
                </CpButton>

                <p class="text-center text-sm text-cafe-500 dark:text-cafe-400">
                    {{ t('register.already_registered') }}
                    <Link
                        :href="route('login')"
                        class="text-cafe-700 dark:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                    >
                        {{ t('auth.login') }}
                    </Link>
                </p>
            </form>
        </div>
    </GuestLayout>
</template>
