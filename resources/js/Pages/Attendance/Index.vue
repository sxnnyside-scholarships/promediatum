<script setup>
/**
 * Attendance Index — Bulk marking by date
 */

import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const { t } = useTranslations();

const props = defineProps({
    group: Object,
    date: String,
    students: { type: Array, default: () => [] },
});

const selectedDate = ref(props.date);

// Initialize records from existing data
const records = ref(
    props.students.map((s) => ({
        student_id: s.id,
        full_name: s.full_name,
        status: s.status || 'present',
    })),
);

// When date changes, reload
watch(selectedDate, (val) => {
    router.get(
        route('attendance.index', props.group.slug),
        { date: val },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
});

function setAllStatus(status) {
    records.value.forEach((r) => {
        r.status = status;
    });
}

function cycleStatus(record) {
    const order = ['present', 'absent', 'justified'];
    const idx = order.indexOf(record.status);
    record.status = order[(idx + 1) % order.length];
}

const form = useForm({});
const saving = ref(false);

function save() {
    saving.value = true;
    router.post(
        route('attendance.store', props.group.slug),
        {
            date: selectedDate.value,
            records: records.value.map((r) => ({
                student_id: r.student_id,
                status: r.status,
            })),
        },
        {
            preserveScroll: true,
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function statusClass(status) {
    return (
        {
            present: 'bg-state-success/10 text-state-success',
            absent: 'bg-state-danger/10 text-state-danger',
            justified: 'bg-state-warning/10 text-state-warning',
        }[status] || ''
    );
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('attendance.title') + ' — ' + group.name" />

        <div>
            <!-- Back -->
            <div class="mb-6">
                <Link
                    :href="route('groups.show', group.slug)"
                    class="text-sm text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 dark:hover:text-cafe-200 underline underline-offset-2 transition-colors duration-150"
                >
                    ← {{ group.name }}
                </Link>
            </div>

            <div class="flex items-center justify-between mb-6">
                <h1 class="font-serif">{{ t('attendance.title') }}</h1>
                <div class="flex items-center gap-3">
                    <input
                        v-model="selectedDate"
                        type="date"
                        class="cp-input text-sm"
                    />
                </div>
            </div>

            <!-- Quick actions -->
            <div class="flex items-center gap-2 mb-4">
                <CpButton type="button" variant="ghost" @click="setAllStatus('present')">
                    {{ t('attendance.all_present') }}
                </CpButton>
                <CpButton type="button" variant="ghost" @click="setAllStatus('absent')">
                    {{ t('attendance.all_absent') }}
                </CpButton>
            </div>

            <!-- Student list -->
            <div v-if="records.length === 0" class="text-center py-12">
                <p class="text-sm text-cafe-500 dark:text-cafe-400">{{ t('groups.no_students') }}</p>
            </div>

            <div v-else class="overflow-hidden rounded-subtle border border-cafe-200 dark:border-cafe-700">
                <table class="w-full text-scan-body">
                    <thead>
                        <tr class="bg-cafe-100 dark:bg-surface-dark-2 text-left">
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200">{{ t('students.name') }}</th>
                            <th class="px-scan-pad py-2 font-medium text-cafe-700 dark:text-cafe-200 text-center">{{ t('attendance.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="record in records"
                            :key="record.student_id"
                            class="border-t border-cafe-200 dark:border-cafe-700 hover:bg-cafe-50 dark:hover:bg-surface-dark-2 transition-colors duration-100"
                        >
                            <td class="px-scan-pad py-2 text-cafe-800 dark:text-cafe-100">
                                {{ record.full_name }}
                            </td>
                            <td class="px-scan-pad py-2 text-center">
                                <button
                                    type="button"
                                    @click="cycleStatus(record)"
                                    class="inline-flex items-center px-3 py-1 rounded-subtle text-xs font-medium transition-colors duration-150 min-w-[80px] justify-center"
                                    :class="statusClass(record.status)"
                                >
                                    {{ t('attendance.' + record.status) }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Save -->
            <div v-if="records.length > 0" class="mt-6">
                <CpButton @click="save" :disabled="saving">
                    {{ t('attendance.save') }}
                </CpButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
