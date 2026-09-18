<script setup>
import { Head, router } from '@inertiajs/vue3';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const { t } = useTranslations();

const props = defineProps({
    codes: {
        type: Array,
        required: true,
    },
    downloadContent: {
        type: String,
        required: true,
    },
});

function downloadCodes() {
    const blob = new Blob([props.downloadContent], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'recovery_tokens.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function acknowledge() {
    router.post(route('recovery-codes.acknowledge'));
}
</script>

<template>
    <GuestLayout>
        <Head :title="t('recovery.title')" />

        <div>
            <!-- Title -->
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-cafe-900 dark:text-cafe-50 font-serif">
                    {{ t('recovery.title') }}
                </h1>
            </div>

            <!-- Warning -->
            <div class="mb-6 bg-accent-50 dark:bg-surface-dark-2 border border-accent-200 dark:border-accent-800 rounded-subtle px-4 py-4">
                <p class="text-sm text-cafe-700 dark:text-cafe-200 leading-relaxed">
                    {{ t('recovery.warning') }}
                </p>
                <p class="mt-2 text-sm font-semibold text-state-danger">
                    {{ t('recovery.warning_strong') }}
                </p>
            </div>

            <!-- Codes grid -->
            <div class="mb-6 bg-cafe-100 dark:bg-surface-dark-1 rounded-subtle px-4 py-4">
                <div class="grid grid-cols-2 gap-3">
                    <div
                        v-for="(code, index) in codes"
                        :key="index"
                        class="font-mono text-sm text-cafe-800 dark:text-cafe-100 bg-white dark:bg-surface-dark-2 rounded-subtle px-3 py-2 text-center tracking-wider"
                    >
                        {{ code }}
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-3">
                <CpButton
                    variant="secondary"
                    type="button"
                    class="w-full justify-center"
                    @click="downloadCodes"
                >
                    {{ t('recovery.download') }}
                </CpButton>

                <CpButton
                    type="button"
                    class="w-full justify-center"
                    @click="acknowledge"
                >
                    {{ t('recovery.confirm') }}
                </CpButton>
            </div>
        </div>
    </GuestLayout>
</template>
