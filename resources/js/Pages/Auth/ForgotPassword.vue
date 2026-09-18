<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const { t } = useTranslations();

defineProps({
    status: String,
});

const form = useForm({
    email: '',
    recovery_code: '',
});

function submit() {
    form.post(route('password.verify'), {
        onFinish: () => form.reset('recovery_code'),
    });
}
</script>

<template>
    <GuestLayout>
        <Head :title="t('forgot.title')" />

        <div>
            <!-- Page title -->
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-cafe-900 dark:text-cafe-50 font-serif">
                    {{ t('forgot.title') }}
                </h1>
                <p class="mt-1 text-sm text-cafe-500 dark:text-cafe-400">
                    {{ t('forgot.subtitle') }}
                </p>
            </div>

            <!-- Status -->
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
                    id="recovery_code"
                    v-model="form.recovery_code"
                    type="text"
                    :label="t('field.recovery_code')"
                    :error="form.errors.recovery_code"
                    required
                    autocomplete="off"
                />

                <CpButton
                    :loading="form.processing"
                    :disabled="form.processing"
                    class="w-full justify-center"
                >
                    {{ t('forgot.submit') }}
                </CpButton>

                <p class="text-center">
                    <Link
                        :href="route('login')"
                        class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                    >
                        {{ t('forgot.back_to_login') }}
                    </Link>
                </p>
            </form>
        </div>
    </GuestLayout>
</template>
