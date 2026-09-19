<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import type { User } from '@/types';

const { t } = useTranslations();

interface Props {
    user?: User | null;
}

const props = defineProps<Props>();

const form = useForm({
    password: '',
});

function submit() {
    form.post(route('session.unlock'), {
        onFinish: () => form.reset('password'),
    });
}

function logout() {
    router.post(route('logout'));
}

// Greeting based on pronoun: "Bienvenido" / "Bienvenida" / "Bienvenide"
const greeting = props.user?.greeting || `Bienvenido/a, ${props.user?.first_name ?? ''}`;
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.unlock')" />

        <div>
            <!-- Greeting -->
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-semibold text-cafe-900 dark:text-cafe-50 font-serif">
                    {{ greeting }}
                </h1>
                <p class="mt-2 text-sm text-cafe-500 dark:text-cafe-400">
                    {{ t('unlock.subtitle') }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <CpInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    :label="t('field.password')"
                    :error="form.errors.password"
                    required
                    autofocus
                    autocomplete="current-password"
                />

                <CpButton
                    :loading="form.processing"
                    :disabled="form.processing"
                    class="w-full justify-center"
                >
                    {{ t('unlock.submit') }}
                </CpButton>

                <p class="text-center">
                    <button
                        type="button"
                        class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                        @click="logout"
                    >
                        {{ t('unlock.logout_instead') }}
                    </button>
                </p>
            </form>
        </div>
    </GuestLayout>
</template>
