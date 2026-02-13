<script setup>
/**
 * Students Create — Form
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpInput from '@/Components/CpInput.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, useForm } from '@inertiajs/vue3';

const { t } = useTranslations();

const form = useForm({
    first_name: '',
    last_name: '',
});

function submit() {
    form.post(route('students.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('students.create')" />

        <div>
            <div class="mb-6">
                <Link
                    :href="route('students.index')"
                    class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                >
                    ← {{ t('students.back') }}
                </Link>
            </div>

            <h1 class="font-serif mb-6">{{ t('students.create') }}</h1>

            <div class="grid grid-cols-1 lg:grid-cols-10 gap-8">
                <!-- Form (70%) -->
                <div class="lg:col-span-7">
                    <form @submit.prevent="submit" class="space-y-5">
                        <CpInput
                            id="first_name"
                            v-model="form.first_name"
                            :label="t('field.first_name')"
                            :error="form.errors.first_name"
                            required
                        />

                        <CpInput
                            id="last_name"
                            v-model="form.last_name"
                            :label="t('field.last_name')"
                            :error="form.errors.last_name"
                            required
                        />

                        <CpButton :disabled="form.processing">
                            {{ t('students.submit') }}
                        </CpButton>
                    </form>
                </div>

                <!-- Context panel (30%) -->
                <aside class="lg:col-span-3">
                    <div class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-5 space-y-3">
                        <h3 class="text-sm font-semibold text-cafe-700 dark:text-cafe-200">
                            {{ t('students.tips_title') }}
                        </h3>
                        <ul class="space-y-2 text-sm text-cafe-500 dark:text-cafe-400">
                            <li>{{ t('students.tip_1') }}</li>
                            <li>{{ t('students.tip_2') }}</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
