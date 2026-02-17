<script setup>
/**
 * Exports/History — Export history & new export form.
 *
 * SCAN-mode table showing past exports with download action.
 * Inline form to generate a new export (group / student / period).
 * Delivery method: Download or Send via Email (requires SMTP config).
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CpSelect from '@/Components/CpSelect.vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import CpIcon from '@/Components/CpIcon.vue';
import { useTranslations } from '@/composables/useTranslations.js';
import { useToast } from '@/composables/useToast.js';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const { t } = useTranslations();
const toast = useToast();
const page = usePage();

const props = defineProps({
    exports:        { type: Array, default: () => [] },
    periods:        { type: Array, default: () => [] },
    groups:         { type: Array, default: () => [] },
    students:       { type: Array, default: () => [] },
    templates:      { type: Array, default: () => [] },
    smtpConfigured: { type: Boolean, default: false },
});

// ── New Export Form ──

const form = useForm({
    type: 'group',
    format: 'csv',
    period_id: '',
    group_id: '',
    student_id: '',
    template_id: '',
    delivery_method: 'download',
    recipient_email: '',
});

const typeOptions = [
    { value: 'group', label: t('exports.type_group') },
    { value: 'student', label: t('exports.type_student') },
    { value: 'period', label: t('exports.type_period') },
];

const formatOptions = [
    { value: 'csv', label: 'CSV' },
    { value: 'json', label: 'JSON' },
    { value: 'xlsx', label: 'Excel (.xlsx)' },
    { value: 'pdf', label: 'PDF' },
];

const periodOptions = computed(() => [
    { value: '', label: t('exports.select_period') },
    ...props.periods.map(p => ({ value: String(p.id), label: p.name })),
]);

const filteredGroups = computed(() => {
    if (!form.period_id) return [];
    return props.groups.filter(g => String(g.period_id) === String(form.period_id));
});

const groupOptions = computed(() => [
    { value: '', label: t('exports.select_group') },
    ...filteredGroups.value.map(g => ({ value: String(g.id), label: g.name })),
]);

const studentOptions = computed(() => [
    { value: '', label: t('exports.select_student') },
    ...props.students.map(s => ({ value: String(s.id), label: `${s.last_name}, ${s.first_name}` })),
]);

const filteredTemplates = computed(() => {
    return props.templates.filter(tmpl => tmpl.type === form.type);
});

const templateOptions = computed(() => [
    { value: '', label: t('exports.no_template') },
    ...filteredTemplates.value.map(tmpl => ({
        value: String(tmpl.id),
        label: tmpl.name + (tmpl.is_default ? ` (${t('templates.default_yes')})` : ''),
    })),
]);

const needsGroup = computed(() => form.type === 'group' || form.type === 'student');
const needsStudent = computed(() => form.type === 'student');

// Reset dependent fields when type changes
watch(() => form.type, () => {
    if (form.type === 'period') {
        form.group_id = '';
        form.student_id = '';
    } else if (form.type === 'group') {
        form.student_id = '';
    }
});

// Reset group when period changes
watch(() => form.period_id, () => {
    form.group_id = '';
    form.student_id = '';
});

const smtpAvailable = computed(() => props.smtpConfigured || page.props.smtp_configured);

const submitting = ref(false);

function submitExport() {
    submitting.value = true;

    const data = {
        type: form.type,
        format: form.format,
        period_id: form.period_id,
        delivery_method: form.delivery_method,
    };

    if (needsGroup.value && form.group_id) {
        data.group_id = form.group_id;
    }

    if (needsStudent.value && form.student_id) {
        data.student_id = form.student_id;
    }

    if (form.template_id) {
        data.template_id = form.template_id;
    }

    if (form.delivery_method === 'email') {
        data.recipient_email = form.recipient_email;

        // Email delivery — use JSON response, not blob
        axios.post(route('exports.store'), data)
            .then(response => {
                if (response.data.success) {
                    toast.success(t('exports.email_queued'));
                } else {
                    toast.error(response.data.message || t('exports.email_error'));
                }
                router.reload({ only: ['exports'] });
            })
            .catch(err => {
                const msg = err.response?.data?.message || t('exports.email_error');
                toast.error(msg);
            })
            .finally(() => {
                submitting.value = false;
            });
        return;
    }

    // Download delivery — blob response
    axios.post(route('exports.store'), data, { responseType: 'blob' })
        .then(response => {
            // Extract filename from Content-Disposition header
            const disposition = response.headers['content-disposition'];
            let fileName = 'export';
            if (disposition) {
                const match = disposition.match(/filename="?([^";\n]+)"?/);
                if (match) fileName = match[1];
            }

            // Trigger browser download
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', fileName);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            toast.success(t('exports.download_success'));

            // Refresh history
            router.reload({ only: ['exports'] });
        })
        .catch(() => {
            toast.error(t('exports.download_error'));
        })
        .finally(() => {
            submitting.value = false;
        });
}

function downloadExport(exp) {
    window.location.href = route('exports.download', exp.id);
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatBadge(format) {
    return format.toUpperCase();
}

function typeBadge(type) {
    const map = {
        group: t('exports.type_group'),
        student: t('exports.type_student'),
        period: t('exports.type_period'),
    };
    return map[type] || type;
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('exports.title')" />

        <div>
            <h1 class="font-serif mb-6">{{ t('exports.title') }}</h1>

            <!-- New Export Form -->
            <div class="p-5 rounded-subtle border border-cafe-200 dark:border-cafe-700 bg-white dark:bg-surface-dark-2 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-medium text-cafe-700 dark:text-cafe-200">{{ t('exports.new_export') }}</h2>
                    <a
                        :href="route('exports.templates.index')"
                        class="text-xs text-accent-600 dark:text-accent-400 hover:text-accent-800 dark:hover:text-accent-200 underline underline-offset-2 transition-colors duration-150"
                    >
                        {{ t('exports.manage_templates') }}
                    </a>
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-36">
                        <CpSelect
                            id="export_type"
                            v-model="form.type"
                            :label="t('exports.export_type')"
                            :options="typeOptions"
                        />
                    </div>

                    <div class="w-36">
                        <CpSelect
                            id="export_format"
                            v-model="form.format"
                            :label="t('exports.format')"
                            :options="formatOptions"
                        />
                    </div>

                    <div class="w-44">
                        <CpSelect
                            id="export_period"
                            v-model="form.period_id"
                            :label="t('exports.period')"
                            :options="periodOptions"
                        />
                    </div>

                    <div v-if="needsGroup" class="w-44">
                        <CpSelect
                            id="export_group"
                            v-model="form.group_id"
                            :label="t('exports.group')"
                            :options="groupOptions"
                        />
                    </div>

                    <div v-if="needsStudent" class="w-52">
                        <CpSelect
                            id="export_student"
                            v-model="form.student_id"
                            :label="t('exports.student')"
                            :options="studentOptions"
                        />
                    </div>

                    <div v-if="filteredTemplates.length > 0" class="w-44">
                        <CpSelect
                            id="export_template"
                            v-model="form.template_id"
                            :label="t('exports.template')"
                            :options="templateOptions"
                        />
                    </div>

                    <!-- Delivery Method -->
                    <div class="w-44">
                        <span class="cp-label">{{ t('exports.delivery') }}</span>
                        <div class="inline-flex rounded-subtle border border-cafe-200 dark:border-cafe-700 bg-white dark:bg-surface-dark-2 p-0.5 mt-1">
                            <button
                                type="button"
                                @click="form.delivery_method = 'download'"
                                class="flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-subtle transition-colors duration-150"
                                :class="form.delivery_method === 'download'
                                    ? 'bg-accent-400 text-white dark:bg-accent-500'
                                    : 'text-cafe-600 dark:text-cafe-300 hover:text-cafe-800 dark:hover:text-cafe-100'"
                            >
                                <CpIcon name="download" :size="14" />
                                {{ t('exports.download') }}
                            </button>
                            <button
                                type="button"
                                @click="form.delivery_method = 'email'"
                                :disabled="!smtpAvailable"
                                class="flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-subtle transition-colors duration-150"
                                :class="[
                                    form.delivery_method === 'email'
                                        ? 'bg-accent-400 text-white dark:bg-accent-500'
                                        : 'text-cafe-600 dark:text-cafe-300 hover:text-cafe-800 dark:hover:text-cafe-100',
                                    !smtpAvailable ? 'opacity-40 cursor-not-allowed' : ''
                                ]"
                            >
                                <CpIcon name="mail" :size="14" />
                                {{ t('exports.send_email') }}
                            </button>
                        </div>
                        <p v-if="!smtpAvailable" class="text-xs text-cafe-400 dark:text-cafe-500 mt-1">
                            {{ t('exports.smtp_required') }}
                        </p>
                    </div>

                    <!-- Recipient Email (conditional) -->
                    <div v-if="form.delivery_method === 'email'" class="w-52">
                        <CpInput
                            id="recipient_email"
                            v-model="form.recipient_email"
                            type="email"
                            :label="t('exports.recipient_email')"
                            :placeholder="t('exports.recipient_placeholder')"
                        />
                    </div>

                    <div>
                        <CpButton
                            @click="submitExport"
                            :disabled="submitting || !form.period_id || (needsGroup && !form.group_id) || (needsStudent && !form.student_id) || (form.delivery_method === 'email' && !form.recipient_email)"
                        >
                            {{ submitting ? t('exports.generating') : (form.delivery_method === 'email' ? t('exports.send_email') : t('exports.generate')) }}
                        </CpButton>
                    </div>
                </div>

                <div v-if="form.errors && Object.keys(form.errors).length > 0" class="mt-3">
                    <p v-for="(error, key) in form.errors" :key="key" class="text-xs text-state-danger">
                        {{ error }}
                    </p>
                </div>
            </div>

            <!-- History Table -->
            <div v-if="exports.length === 0" class="text-center py-12">
                <CpIcon name="download" :size="32" class-name="mx-auto mb-3 text-cafe-300 dark:text-cafe-600" />
                <p class="text-sm text-cafe-500 dark:text-cafe-400">{{ t('exports.empty') }}</p>
                <p class="text-xs text-cafe-400 dark:text-cafe-500 mt-1">{{ t('exports.empty_hint') }}</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-cafe-200 dark:border-cafe-700">
                            <th class="text-left py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('exports.col_type') }}
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('exports.col_format') }}
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('exports.col_context') }}
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('exports.col_template') }}
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('exports.col_delivery') }}
                            </th>
                            <th class="text-left py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('exports.col_date') }}
                            </th>
                            <th class="text-right py-2 px-3 text-xs font-medium text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                {{ t('exports.col_action') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="exp in exports"
                            :key="exp.id"
                            class="border-b border-cafe-100 dark:border-cafe-800 hover:bg-cafe-50 dark:hover:bg-surface-dark-3 transition-colors duration-100"
                        >
                            <td class="py-2.5 px-3">
                                <span class="text-xs font-medium px-1.5 py-0.5 rounded-subtle bg-cafe-200 dark:bg-surface-dark-3 text-cafe-600 dark:text-cafe-400">
                                    {{ typeBadge(exp.type) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="text-xs font-mono font-medium px-1.5 py-0.5 rounded-subtle"
                                    :class="{
                                        'bg-accent-100 dark:bg-accent-900/30 text-accent-700 dark:text-accent-300': exp.format === 'xlsx',
                                        'bg-state-info/10 text-state-info': exp.format === 'json',
                                        'bg-state-success/10 text-state-success': exp.format === 'csv',
                                        'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300': exp.format === 'pdf',
                                    }"
                                >
                                    {{ formatBadge(exp.format) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-cafe-700 dark:text-cafe-200 max-w-xs truncate">
                                {{ exp.context_label }}
                            </td>
                            <td class="py-2.5 px-3 text-xs text-cafe-500 dark:text-cafe-400">
                                {{ exp.template_name || '—' }}
                            </td>
                            <td class="py-2.5 px-3 text-xs text-cafe-500 dark:text-cafe-400">
                                <span v-if="exp.sent_via_email" class="inline-flex items-center gap-1">
                                    <CpIcon name="mail" :size="12" />
                                    {{ exp.recipient_email }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1">
                                    <CpIcon name="download" :size="12" />
                                    {{ t('exports.download') }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-cafe-500 dark:text-cafe-400 text-xs">
                                {{ formatDate(exp.created_at) }}
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                <button
                                    v-if="exp.can_download"
                                    @click="downloadExport(exp)"
                                    class="text-xs text-accent-600 dark:text-accent-400 hover:text-accent-800 dark:hover:text-accent-200 transition-colors duration-150"
                                >
                                    {{ t('exports.download') }}
                                </button>
                                <span v-else class="text-xs text-cafe-400 dark:text-cafe-600">
                                    {{ t('exports.unavailable') }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
