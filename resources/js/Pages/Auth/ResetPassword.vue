<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const { t } = useTranslations();

const form = useForm({
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <GuestLayout>
        <Head :title="t('reset.title')" />

        <div>
            <!-- Page title -->
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-cafe-900 dark:text-cafe-50 font-serif">
                    {{ t('reset.title') }}
                </h1>
                <p class="mt-1 text-sm text-cafe-500 dark:text-cafe-400">
                    {{ t('reset.subtitle') }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <CpInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    :label="t('field.new_password')"
                    :error="form.errors.password"
                    required
                    autofocus
                    autocomplete="new-password"
                />

                <CpInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    :label="t('field.new_password_confirmation')"
                    :error="form.errors.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <CpButton
                    :loading="form.processing"
                    :disabled="form.processing"
                    class="w-full justify-center"
                >
                    {{ t('reset.submit') }}
                </CpButton>
            </form>
        </div>
    </GuestLayout>
</template>
