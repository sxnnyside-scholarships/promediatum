<script setup>
/**
 * Groups Create — Form
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpInput from '@/Components/CpInput.vue';
import CpSelect from '@/Components/CpSelect.vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { Head, Link, useForm } from '@inertiajs/vue3';

const { t } = useTranslations();

const props = defineProps({
    periods: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    subject: '',
    educational_level: '',
    period_id: '',
});

const periodOptions = props.periods.map(p => ({ value: String(p.id), label: p.name }));

function submit() {
    form.post(route('groups.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('groups.create')" />

        <div>
            <div class="mb-6">
                <Link
                    :href="route('groups.index')"
                    class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                >
                    ← {{ t('groups.back') }}
                </Link>
            </div>

            <h1 class="font-serif mb-6">{{ t('groups.create') }}</h1>

            <div class="grid grid-cols-1 lg:grid-cols-10 gap-8">
                <!-- Form (70%) -->
                <div class="lg:col-span-7">
                    <form @submit.prevent="submit" class="space-y-5">
                        <CpInput
                            id="name"
                            v-model="form.name"
                            :label="t('groups.name')"
                            :error="form.errors.name"
                            required
                        />

                        <CpInput
                            id="subject"
                            v-model="form.subject"
                            :label="t('groups.subject')"
                            :error="form.errors.subject"
                        />

                        <CpInput
                            id="educational_level"
                            v-model="form.educational_level"
                            :label="t('groups.educational_level')"
                            :error="form.errors.educational_level"
                        />

                        <CpSelect
                            id="period_id"
                            v-model="form.period_id"
                            :label="t('groups.period')"
                            :options="periodOptions"
                            :placeholder="t('groups.select_period')"
                            :error="form.errors.period_id"
                            required
                        />

                        <CpButton :disabled="form.processing">
                            {{ t('groups.submit') }}
                        </CpButton>
                    </form>
                </div>

                <!-- Context panel (30%) -->
                <aside class="lg:col-span-3">
                    <div class="rounded-subtle bg-cafe-100/60 dark:bg-surface-dark-1 p-5 space-y-3">
                        <h3 class="text-sm font-semibold text-cafe-700 dark:text-cafe-200">
                            {{ t('groups.tips_title') }}
                        </h3>
                        <ul class="space-y-2 text-sm text-cafe-500 dark:text-cafe-400">
                            <li>{{ t('groups.tip_1') }}</li>
                            <li>{{ t('groups.tip_2') }}</li>
                            <li>{{ t('groups.tip_3') }}</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
