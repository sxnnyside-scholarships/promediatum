<script setup>
/**
 * Periods Create — Read Mode (form)
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpInput from '@/Components/CpInput.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, useForm } from '@inertiajs/vue3';

const { t } = useTranslations();

const form = useForm({
    name: '',
    start_date: '',
    end_date: '',
});

function submit() {
    form.post(route('periods.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('periods.create')" />

        <div>
            <!-- Back link -->
            <div class="mb-6">
                <Link
                    :href="route('periods.index')"
                    class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                >
                    ← {{ t('periods.back') }}
                </Link>
            </div>

            <!-- Title -->
            <div class="mb-8">
                <h1 class="font-serif">{{ t('periods.create') }}</h1>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-10 gap-8">
                <!-- Form fields (70%) -->
                <div class="lg:col-span-7 space-y-5">
                    <!-- Name — full width -->
                    <CpInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        :label="t('periods.name')"
                        :error="form.errors.name"
                        required
                        autofocus
                    />

                    <!-- Dates — side by side -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <CpInput
                            id="start_date"
                            v-model="form.start_date"
                            type="date"
                            :label="t('periods.start_date')"
                            :error="form.errors.start_date"
                            required
                        />
                        <CpInput
                            id="end_date"
                            v-model="form.end_date"
                            type="date"
                            :label="t('periods.end_date')"
                            :error="form.errors.end_date"
                            required
                        />
                    </div>

                    <CpButton
                        :loading="form.processing"
                        :disabled="form.processing"
                    >
                        {{ t('periods.submit') }}
                    </CpButton>
                </div>

                <!-- Context panel (30%) -->
                <aside class="lg:col-span-3">
                    <div class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-5 space-y-3">
                        <h3 class="text-sm font-semibold text-cafe-700 dark:text-cafe-200">
                            {{ t('periods.tips_title') }}
                        </h3>
                        <ul class="space-y-2 text-sm text-cafe-500 dark:text-cafe-400">
                            <li>{{ t('periods.tip_1') }}</li>
                            <li>{{ t('periods.tip_2') }}</li>
                        </ul>
                    </div>
                </aside>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
