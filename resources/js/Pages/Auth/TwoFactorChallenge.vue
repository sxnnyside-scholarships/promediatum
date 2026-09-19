<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Key1Regular, SafeShield2Regular } from '@mingcute/vue/core-regular';
import { nextTick, ref } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const { t } = useTranslations();

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

function toggleRecovery() {
    recovery.value = !recovery.value;
    form.clearErrors();
    form.reset();

    nextTick(() => {
        const input = document.getElementById(recovery.value ? 'recovery_code' : 'code');
        input?.focus();
    });
}

function submit() {
    form.post(route('two-factor.challenge'), {
        onFinish: () => {
            form.reset('code', 'recovery_code');
        },
    });
}

function cancel() {
    router.post(route('two-factor.cancel'));
}
</script>

<template>
    <GuestLayout>
        <Head :title="t('two_factor.title')" />

        <div>
            <!-- Header -->
            <div class="mb-8 text-center">
                <div class="mx-auto w-12 h-12 rounded-2xl bg-amber-100 dark:bg-surface-dark-2 text-amber-700 dark:text-amber-400 flex items-center justify-center mb-3 shadow-xs border border-amber-200/60 dark:border-surface-dark-3">
                    <SafeShield2Regular v-if="!recovery" class="w-6 h-6" />
                    <Key1Regular v-else class="w-6 h-6" />
                </div>

                <h1 class="text-2xl font-bold text-cafe-900 dark:text-cafe-50 font-serif">
                    {{ t('two_factor.title') }}
                </h1>
                <p class="mt-2 text-sm text-cafe-600 dark:text-cafe-300">
                    {{ recovery ? t('two_factor.recovery_description') : t('two_factor.description') }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- TOTP Code Input -->
                <div v-if="!recovery">
                    <CpInput
                        id="code"
                        v-model="form.code"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        :label="t('two_factor.code_label')"
                        :error="form.errors.code"
                        required
                        autofocus
                        autocomplete="one-time-code"
                        placeholder="123456"
                        class="tracking-widest font-mono text-center text-lg"
                    />
                </div>

                <!-- Recovery Code Input -->
                <div v-else>
                    <CpInput
                        id="recovery_code"
                        v-model="form.recovery_code"
                        type="text"
                        maxlength="10"
                        :label="t('two_factor.recovery_code_label')"
                        :error="form.errors.recovery_code"
                        required
                        autofocus
                        autocomplete="off"
                        placeholder="ABCDEF12"
                        class="tracking-widest font-mono uppercase text-center text-base"
                    />
                </div>

                <CpButton
                    :loading="form.processing"
                    :disabled="form.processing"
                    class="w-full justify-center"
                >
                    {{ t('two_factor.verify_button') }}
                </CpButton>

                <!-- Switch between TOTP and Recovery Code -->
                <div class="pt-2 text-center space-y-2">
                    <button
                        type="button"
                        class="text-xs font-semibold text-accent-600 hover:text-accent-700 dark:text-accent-400 dark:hover:text-accent-300 transition-colors"
                        @click="toggleRecovery"
                    >
                        {{ recovery ? t('two_factor.use_app') : t('two_factor.use_recovery') }}
                    </button>

                    <div>
                        <button
                            type="button"
                            class="text-xs text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 transition-colors"
                            @click="cancel"
                        >
                            {{ t('two_factor.cancel') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
