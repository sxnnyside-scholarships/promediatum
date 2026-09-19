<script setup lang="ts">
/**
 * Exports/History — Export generator and audit history vault.
 *
 * CAFÉ PEDAGÓGICO DESIGN SYSTEM
 * - Dynamic preloading from Student, Group, or Period views
 * - 4-step guided generator: Scope -> Context -> Format -> Delivery/Template
 * - Visual format cards (XLSX, PDF, CSV, JSON) with distinct identities
 * - Interactive history table with quick filters and 1-click re-download
 */

import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
// MingCute Regular Icons
import {
    AlertRegular,
    Calendar2Regular,
    CheckCircleRegular,
    CheckRegular,
    CloseCircleRegular,
    DocRegular,
    Download2Regular,
    ExternalLinkRegular,
    FileCodeRegular,
    FileDownloadRegular,
    FileExportRegular,
    GroupRegular,
    InformationRegular,
    Layout11Regular,
    MailRegular,
    MailSendRegular,
    Refresh1Regular,
    SearchRegular,
    Settings1Regular,
    TableRegular,
    User1Regular,
} from '@mingcute/vue/core-regular';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import CpSelect from '@/Components/CpSelect.vue';
import { useToast } from '@/composables/useToast';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ExportHistory, ExportTemplate, Group, PageProps, Period, Student } from '@/types';

interface PrefillData {
    type?: string;
    group_id?: number | string;
    student_id?: number | string;
    period_id?: number | string;
    template_id?: number | string;
    format?: string;
}

interface Props {
    exports?: ExportHistory[];
    periods?: Period[];
    groups?: Group[];
    students?: Student[];
    templates?: ExportTemplate[];
    smtpConfigured?: boolean;
    prefill?: PrefillData;
}

const { t } = useTranslations();
const toast = useToast();
const page = usePage<PageProps>();

const props = withDefaults(defineProps<Props>(), {
    exports: () => [],
    periods: () => [],
    groups: () => [],
    students: () => [],
    templates: () => [],
    smtpConfigured: false,
    prefill: () => ({}),
});

// ── Smart Pre-loading & Form State ──

const form = useForm({
    type: 'group',
    format: 'xlsx',
    period_id: '',
    group_id: '',
    student_id: '',
    template_id: '',
    delivery_method: 'download',
    recipient_email: '',
});

const prefilledBanner = ref<{ type: string; title: string; subtitle: string } | null>(null);
const submitting = ref(false);

// Format Options with rich metadata & colors
const formats = [
    {
        id: 'xlsx',
        name: 'Excel (.xlsx)',
        desc: t('exports.format_xlsx_desc'),
        icon: TableRegular,
        colorClass: 'text-emerald-700 dark:text-emerald-300',
        bgClass: 'bg-emerald-50 dark:bg-emerald-950/40',
        borderActiveClass: 'border-emerald-500 ring-2 ring-emerald-500/20',
        badgeBg: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200',
    },
    {
        id: 'pdf',
        name: 'PDF Oficial (.pdf)',
        desc: t('exports.format_pdf_desc'),
        icon: DocRegular,
        colorClass: 'text-rose-700 dark:text-rose-300',
        bgClass: 'bg-rose-50 dark:bg-rose-950/40',
        borderActiveClass: 'border-rose-500 ring-2 ring-rose-500/20',
        badgeBg: 'bg-rose-100 dark:bg-rose-900/40 text-rose-800 dark:text-rose-200',
    },
    {
        id: 'csv',
        name: 'CSV (.csv)',
        desc: t('exports.format_csv_desc'),
        icon: Layout11Regular,
        colorClass: 'text-amber-700 dark:text-amber-300',
        bgClass: 'bg-amber-50 dark:bg-amber-950/40',
        borderActiveClass: 'border-amber-500 ring-2 ring-amber-500/20',
        badgeBg: 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200',
    },
    {
        id: 'json',
        name: 'JSON (.json)',
        desc: t('exports.format_json_desc'),
        icon: FileCodeRegular,
        colorClass: 'text-indigo-700 dark:text-indigo-300',
        bgClass: 'bg-indigo-50 dark:bg-indigo-950/40',
        borderActiveClass: 'border-indigo-500 ring-2 ring-indigo-500/20',
        badgeBg: 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200',
    },
];

// Scope types metadata
const scopeTypes = [
    {
        id: 'group',
        label: t('exports.type_group'),
        desc: t('exports.type_group_desc'),
        icon: GroupRegular,
    },
    {
        id: 'student',
        label: t('exports.type_student'),
        desc: t('exports.type_student_desc'),
        icon: User1Regular,
    },
    {
        id: 'period',
        label: t('exports.type_period'),
        desc: t('exports.type_period_desc'),
        icon: Calendar2Regular,
    },
];

// Cascading Options
const periodOptions = computed(() => [
    { value: '', label: t('exports.select_period') },
    ...props.periods.map((p) => ({ value: String(p.id), label: p.name })),
]);

const filteredGroups = computed(() => {
    if (!form.period_id) return [];
    return props.groups.filter((g) => String(g.period_id) === String(form.period_id));
});

const groupOptions = computed(() => [
    { value: '', label: t('exports.select_group') },
    ...filteredGroups.value.map((g) => ({ value: String(g.id), label: g.name })),
]);

// Filtered students by selected group if group is picked, else all students
const filteredStudents = computed(() => {
    if (!form.group_id) return props.students;
    return props.students.filter((s) => {
        if (!s.groups?.length) return true;
        return s.groups.some((g) => String(g.id) === String(form.group_id));
    });
});

const studentOptions = computed(() => [
    { value: '', label: t('exports.select_student') },
    ...filteredStudents.value.map((s) => ({
        value: String(s.id),
        label: `${s.last_name}, ${s.first_name}${s.email ? ` (${s.email})` : ''}`,
    })),
]);

const selectedStudentObj = computed(() => {
    if (!form.student_id) return null;
    return props.students.find((s) => String(s.id) === String(form.student_id)) || null;
});

const filteredTemplates = computed(() => {
    return props.templates.filter((tmpl) => tmpl.type === form.type);
});

const templateOptions = computed(() => [
    { value: '', label: t('exports.no_template') },
    ...filteredTemplates.value.map((tmpl) => ({
        value: String(tmpl.id),
        label: tmpl.name + (tmpl.is_default ? ` (${t('templates.default_yes')})` : ''),
    })),
]);

const needsGroup = computed(() => form.type === 'group' || form.type === 'student');
const needsStudent = computed(() => form.type === 'student');
const smtpAvailable = computed(() => props.smtpConfigured || page.props.smtp_configured);

// Reset dependent selections when type changes manually
function selectScope(typeId: string) {
    if (form.type === typeId) return;
    form.type = typeId;
    if (typeId === 'period') {
        form.group_id = '';
        form.student_id = '';
    } else if (typeId === 'group') {
        form.student_id = '';
    }
    // Check if current template fits new type
    if (form.template_id) {
        const fits = filteredTemplates.value.some(
            (tmpl) => String(tmpl.id) === String(form.template_id),
        );
        if (!fits) form.template_id = '';
    }
}

// Reset groups and students when period changes
watch(
    () => form.period_id,
    (newVal, oldVal) => {
        if (oldVal && newVal !== oldVal) {
            form.group_id = '';
            form.student_id = '';
        }
    },
);

// Reset student if group changes
watch(
    () => form.group_id,
    (newVal, oldVal) => {
        if (oldVal && newVal !== oldVal) {
            form.student_id = '';
        }
    },
);

// Auto-fill student email as recipient suggestion if email delivery selected
watch(
    () => form.delivery_method,
    (method) => {
        if (method === 'email' && !form.recipient_email && selectedStudentObj.value?.email) {
            form.recipient_email = selectedStudentObj.value.email;
        }
    },
);

// ── Lifecycle: Initialize & Pre-load from URL / Props ──

onMounted(() => {
    const urlParams =
        typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;

    const rawType = props.prefill?.type || urlParams?.get('type');
    const rawPeriodId = props.prefill?.period_id || urlParams?.get('period_id');
    const rawGroupId = props.prefill?.group_id || urlParams?.get('group_id');
    const rawStudentId = props.prefill?.student_id || urlParams?.get('student_id');
    const rawFormat = props.prefill?.format || urlParams?.get('format');
    const rawTemplateId = props.prefill?.template_id || urlParams?.get('template_id');

    if (rawFormat && ['xlsx', 'pdf', 'csv', 'json'].includes(rawFormat)) {
        form.format = rawFormat;
    }

    if (rawTemplateId) {
        form.template_id = String(rawTemplateId);
    }

    // 1. Preload from Student
    if (rawStudentId || rawType === 'student') {
        form.type = 'student';
        if (rawStudentId) {
            form.student_id = String(rawStudentId);
            const st = props.students.find((s) => String(s.id) === String(rawStudentId));
            if (st) {
                prefilledBanner.value = {
                    type: 'student',
                    title: `${st.last_name}, ${st.first_name}`,
                    subtitle: t('exports.type_student'),
                };

                // Deduce group and period if available
                if (st.groups && st.groups.length > 0) {
                    const matchedGroup = rawGroupId
                        ? st.groups.find((g) => String(g.id) === String(rawGroupId)) || st.groups[0]
                        : st.groups[0];
                    form.group_id = String(matchedGroup.id);
                    form.period_id = String(matchedGroup.period_id);
                }
            }
        }
    }
    // 2. Preload from Group
    else if (rawGroupId || rawType === 'group') {
        form.type = 'group';
        if (rawGroupId) {
            form.group_id = String(rawGroupId);
            const gr = props.groups.find((g) => String(g.id) === String(rawGroupId));
            if (gr) {
                prefilledBanner.value = {
                    type: 'group',
                    title: gr.name,
                    subtitle: t('exports.type_group'),
                };
                if (gr.period_id) {
                    form.period_id = String(gr.period_id);
                }
            }
        }
    }
    // 3. Preload from Period
    else if (rawPeriodId || rawType === 'period') {
        form.type = 'period';
        if (rawPeriodId) {
            form.period_id = String(rawPeriodId);
            const pr = props.periods.find((p) => String(p.id) === String(rawPeriodId));
            if (pr) {
                prefilledBanner.value = {
                    type: 'period',
                    title: pr.name,
                    subtitle: t('exports.type_period'),
                };
            }
        }
    }

    // Fallback: If no period selected yet, pick the first available active period
    if (!form.period_id && props.periods.length > 0) {
        form.period_id = String(props.periods[0].id);
    }
});

function clearPrefill() {
    prefilledBanner.value = null;
    form.type = 'group';
    form.period_id = props.periods.length ? String(props.periods[0].id) : '';
    form.group_id = '';
    form.student_id = '';
    form.template_id = '';
    if (typeof window !== 'undefined' && window.history.replaceState) {
        window.history.replaceState({}, '', window.location.pathname);
    }
    toast.success(t('exports.clear_prefill'));
}

// ── Export Submission ──

const isFormValid = computed(() => {
    if (!form.period_id) return false;
    if (needsGroup.value && !form.group_id) return false;
    if (needsStudent.value && !form.student_id) return false;
    if (form.delivery_method === 'email' && !form.recipient_email) return false;
    return true;
});

function submitExport() {
    if (!isFormValid.value || submitting.value) return;

    submitting.value = true;

    const data: Record<string, any> = {
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

        axios
            .post(route('exports.store'), data)
            .then((response) => {
                if (response.data.success) {
                    toast.success(t('exports.email_queued'));
                } else {
                    toast.error(response.data.message || t('exports.email_error'));
                }
                router.reload({ only: ['exports'] });
            })
            .catch((err) => {
                const msg = err.response?.data?.message || t('exports.email_error');
                toast.error(msg);
            })
            .finally(() => {
                submitting.value = false;
            });
        return;
    }

    // Direct download (blob response)
    axios
        .post(route('exports.store'), data, { responseType: 'blob' })
        .then((response) => {
            const disposition = response.headers['content-disposition'];
            let fileName = `promediatum_${form.type}_${Date.now()}.${form.format}`;
            if (disposition) {
                const match = disposition.match(/filename="?([^";\n]+)"?/);
                if (match) fileName = match[1];
            }

            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', fileName);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            toast.success(t('exports.download_success'));
            router.reload({ only: ['exports'] });
        })
        .catch(() => {
            toast.error(t('exports.download_error'));
        })
        .finally(() => {
            submitting.value = false;
        });
}

function downloadExport(exp: ExportHistory) {
    window.location.href = route('exports.download', exp.id);
}

// ── Export History Filtering & Search ──

const historySearch = ref('');
const historyTypeFilter = ref('all');
const historyFormatFilter = ref('all');

const filteredExports = computed(() => {
    return props.exports.filter((exp) => {
        // Type filter
        if (historyTypeFilter.value !== 'all' && exp.type !== historyTypeFilter.value) {
            return false;
        }
        // Format filter
        if (historyFormatFilter.value !== 'all' && exp.format !== historyFormatFilter.value) {
            return false;
        }
        // Search query
        if (historySearch.value.trim()) {
            const q = historySearch.value.toLowerCase();
            const matchName =
                exp.file_name?.toLowerCase().includes(q) || exp.filename?.toLowerCase().includes(q);
            const matchContext = exp.context_label?.toLowerCase().includes(q);
            const matchTemplate = exp.template_name?.toLowerCase().includes(q);
            const matchEmail = exp.recipient_email?.toLowerCase().includes(q);
            return matchName || matchContext || matchTemplate || matchEmail;
        }
        return true;
    });
});

function formatDate(dateStr?: string | null): string {
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

function getFormatMeta(format: string) {
    return formats.find((f) => f.id === format) || formats[0];
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('exports.title')" />

        <div class="space-y-8 pb-12">
            <!-- Header card with restful pedagogical gradient -->
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 rounded-xl bg-accent-100 dark:bg-accent-950/60 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/50 shrink-0">
                            <FileExportRegular class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                                {{ t('exports.title') }}
                            </h1>
                            <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5 leading-relaxed">
                                {{ t('exports.subtitle') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <Link
                            :href="route('exports.templates.index')"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-cafe-700 dark:text-cafe-200 hover:text-accent-700 dark:hover:text-accent-300 bg-white dark:bg-surface-dark-2 border border-cafe-200/80 dark:border-cafe-700 hover:border-accent-300 dark:hover:border-accent-700 shadow-2xs transition-all"
                        >
                            <Settings1Regular class="w-4 h-4 text-cafe-500" />
                            <span>{{ t('exports.manage_templates') }}</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Smart Pre-load Alert Banner (if arrived from Student, Group or Period) -->
            <div
                v-if="prefilledBanner"
                class="rounded-2xl border border-accent-200/80 dark:border-accent-800/70 bg-gradient-to-r from-accent-50/80 via-white to-accent-50/30 dark:from-accent-950/30 dark:via-surface-dark-2 dark:to-surface-dark-1 p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fadeIn"
            >
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-accent-100 dark:bg-accent-900/40 text-accent-700 dark:text-accent-300">
                        <CheckCircleRegular class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-accent-200/60 dark:bg-accent-900/60 text-accent-800 dark:text-accent-200">
                                {{ t('exports.prefilled_badge') }}
                            </span>
                            <span class="text-xs text-cafe-500 dark:text-cafe-400">
                                {{ t('exports.prefilled_desc') }}
                            </span>
                        </div>
                        <p class="text-sm font-semibold text-cafe-800 dark:text-cafe-100 mt-1">
                            {{ prefilledBanner.subtitle }}: <span class="font-bold text-accent-700 dark:text-accent-300">{{ prefilledBanner.title }}</span>
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="clearPrefill"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-cafe-600 dark:text-cafe-300 hover:text-cafe-900 dark:hover:text-white bg-white dark:bg-surface-dark-3 border border-cafe-200 dark:border-cafe-700 hover:bg-cafe-50 transition-colors self-start sm:self-auto"
                >
                    <CloseCircleRegular class="w-3.5 h-3.5" />
                    <span>{{ t('exports.clear_prefill') }}</span>
                </button>
            </div>

            <!-- Main Interactive Generator Card -->
            <div class="rounded-3xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 shadow-sm overflow-hidden">
                <div class="p-6 sm:p-8 space-y-8">

                    <!-- Step 1: Export Scope -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="font-serif text-base font-bold text-cafe-900 dark:text-cafe-100 flex items-center gap-2">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-accent-100 dark:bg-accent-950/80 text-accent-700 dark:text-accent-300 text-xs font-bold">1</span>
                                {{ t('exports.step_scope') }}
                            </h2>
                            <span class="text-xs text-cafe-500 dark:text-cafe-400 font-medium">
                                {{ form.type.toUpperCase() }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <button
                                v-for="scope in scopeTypes"
                                :key="scope.id"
                                type="button"
                                @click="selectScope(scope.id)"
                                class="relative text-left p-4 rounded-2xl border transition-all duration-200 group"
                                :class="[
                                    form.type === scope.id
                                        ? 'border-accent-500 bg-accent-50/40 dark:bg-accent-950/20 ring-2 ring-accent-500/20 shadow-xs'
                                        : 'border-cafe-200/90 dark:border-cafe-800 bg-white dark:bg-surface-dark-2 hover:border-cafe-300 dark:hover:border-cafe-700'
                                ]"
                            >
                                <div class="flex items-start justify-between">
                                    <div
                                        class="p-2.5 rounded-xl transition-colors"
                                        :class="form.type === scope.id
                                            ? 'bg-accent-500 text-white shadow-xs'
                                            : 'bg-cafe-100 dark:bg-surface-dark-3 text-cafe-600 dark:text-cafe-300 group-hover:text-accent-600'"
                                    >
                                        <component :is="scope.icon" class="w-5 h-5" />
                                    </div>
                                    <span
                                        v-if="form.type === scope.id"
                                        class="flex items-center justify-center w-5 h-5 rounded-full bg-accent-500 text-white"
                                    >
                                        <CheckRegular class="w-3.5 h-3.5" />
                                    </span>
                                </div>

                                <div class="mt-3">
                                    <h3 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                        {{ scope.label }}
                                    </h3>
                                    <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-1 leading-relaxed">
                                        {{ scope.desc }}
                                    </p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Context Selection (Cascading filters) -->
                    <div class="space-y-4 pt-4 border-t border-cafe-100 dark:border-cafe-800/80">
                        <h2 class="font-serif text-base font-bold text-cafe-900 dark:text-cafe-100 flex items-center gap-2">
                            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-accent-100 dark:bg-accent-950/80 text-accent-700 dark:text-accent-300 text-xs font-bold">2</span>
                            {{ t('exports.step_context') }}
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Period Picker -->
                            <div>
                                <CpSelect
                                    id="export_period"
                                    v-model="form.period_id"
                                    :label="t('exports.period')"
                                    :options="periodOptions"
                                    required
                                />
                            </div>

                            <!-- Group Picker (if scope is Group or Student) -->
                            <div v-if="needsGroup">
                                <CpSelect
                                    id="export_group"
                                    v-model="form.group_id"
                                    :label="t('exports.group')"
                                    :options="groupOptions"
                                    :disabled="!form.period_id"
                                    required
                                />
                            </div>

                            <!-- Student Picker (if scope is Student) -->
                            <div v-if="needsStudent">
                                <CpSelect
                                    id="export_student"
                                    v-model="form.student_id"
                                    :label="t('exports.student')"
                                    :options="studentOptions"
                                    :disabled="!form.group_id"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Context pill summary -->
                        <div v-if="form.period_id && (!needsGroup || form.group_id)" class="flex items-center gap-2 text-xs text-cafe-600 dark:text-cafe-400 bg-cafe-50 dark:bg-surface-dark-2 px-3 py-2 rounded-xl border border-cafe-200/60 dark:border-cafe-700/60">
                            <InformationRegular class="w-4 h-4 text-accent-600 shrink-0" />
                            <span>
                                <strong>{{ t('exports.col_context') }}:</strong>
                                {{ periods.find(p => String(p.id) === String(form.period_id))?.name }}
                                <template v-if="needsGroup && form.group_id">
                                    • {{ groups.find(g => String(g.id) === String(form.group_id))?.name }}
                                </template>
                                <template v-if="needsStudent && selectedStudentObj">
                                    • {{ selectedStudentObj.first_name }} {{ selectedStudentObj.last_name }}
                                </template>
                            </span>
                        </div>
                    </div>

                    <!-- Step 3: Format Selection Cards -->
                    <div class="space-y-3 pt-4 border-t border-cafe-100 dark:border-cafe-800/80">
                        <div class="flex items-center justify-between">
                            <h2 class="font-serif text-base font-bold text-cafe-900 dark:text-cafe-100 flex items-center gap-2">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-accent-100 dark:bg-accent-950/80 text-accent-700 dark:text-accent-300 text-xs font-bold">3</span>
                                {{ t('exports.step_format') }}
                            </h2>
                            <span class="text-xs font-mono font-bold uppercase text-accent-600 dark:text-accent-400">
                                .{{ form.format }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                            <button
                                v-for="fmt in formats"
                                :key="fmt.id"
                                type="button"
                                @click="form.format = fmt.id"
                                class="relative text-left p-4 rounded-2xl border transition-all duration-150 group"
                                :class="[
                                    form.format === fmt.id
                                        ? `${fmt.borderActiveClass} ${fmt.bgClass}`
                                        : 'border-cafe-200/80 dark:border-cafe-800 bg-white dark:bg-surface-dark-2 hover:border-cafe-300 dark:hover:border-cafe-700'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <div class="p-2 rounded-xl" :class="[fmt.badgeBg, fmt.colorClass]">
                                        <component :is="fmt.icon" class="w-5 h-5" />
                                    </div>
                                    <span
                                        v-if="form.format === fmt.id"
                                        class="flex items-center justify-center w-5 h-5 rounded-full bg-accent-600 text-white"
                                    >
                                        <CheckRegular class="w-3.5 h-3.5" />
                                    </span>
                                </div>

                                <h4 class="text-sm font-bold text-cafe-900 dark:text-cafe-100">
                                    {{ fmt.name }}
                                </h4>
                                <p class="text-[11px] text-cafe-500 dark:text-cafe-400 mt-1 line-clamp-2">
                                    {{ fmt.desc }}
                                </p>
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Template & Delivery Method -->
                    <div class="space-y-4 pt-4 border-t border-cafe-100 dark:border-cafe-800/80">
                        <h2 class="font-serif text-base font-bold text-cafe-900 dark:text-cafe-100 flex items-center gap-2">
                            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-accent-100 dark:bg-accent-950/80 text-accent-700 dark:text-accent-300 text-xs font-bold">4</span>
                            {{ t('exports.step_options') }}
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-start">
                            <!-- Template Selector -->
                            <div class="space-y-2">
                                <label class="cp-label flex items-center justify-between">
                                    <span>{{ t('exports.template') }}</span>
                                    <Link
                                        v-if="filteredTemplates.length > 0"
                                        :href="route('exports.templates.index')"
                                        class="text-xs text-accent-600 dark:text-accent-400 hover:underline inline-flex items-center gap-1"
                                    >
                                        <span>{{ t('exports.manage_templates') }}</span>
                                        <ExternalLinkRegular class="w-3 h-3" />
                                    </Link>
                                </label>

                                <CpSelect
                                    id="export_template"
                                    v-model="form.template_id"
                                    :options="templateOptions"
                                />

                                <p v-if="filteredTemplates.length === 0" class="text-xs text-cafe-400 dark:text-cafe-500 flex items-center gap-1">
                                    <InformationRegular class="w-3.5 h-3.5" />
                                    <span>{{ t('exports.no_template') }} ({{ t('templates.default_yes') }})</span>
                                </p>
                            </div>

                            <!-- Delivery Method Selector -->
                            <div class="space-y-2">
                                <span class="cp-label">{{ t('exports.delivery') }}</span>

                                <div class="grid grid-cols-2 gap-2 p-1 rounded-2xl border border-cafe-200 dark:border-cafe-700 bg-cafe-50 dark:bg-surface-dark-2">
                                    <button
                                        type="button"
                                        @click="form.delivery_method = 'download'"
                                        class="flex items-center justify-center gap-2 py-2.5 px-3 text-xs font-semibold rounded-xl transition-all"
                                        :class="form.delivery_method === 'download'
                                            ? 'bg-white dark:bg-surface-dark-3 text-accent-700 dark:text-accent-300 shadow-xs border border-cafe-200/80 dark:border-cafe-700'
                                            : 'text-cafe-600 dark:text-cafe-400 hover:text-cafe-900'"
                                    >
                                        <Download2Regular class="w-4 h-4" />
                                        <span>{{ t('exports.direct_download') }}</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="form.delivery_method = 'email'"
                                        class="flex items-center justify-center gap-2 py-2.5 px-3 text-xs font-semibold rounded-xl transition-all"
                                        :class="form.delivery_method === 'email'
                                            ? 'bg-white dark:bg-surface-dark-3 text-accent-700 dark:text-accent-300 shadow-xs border border-cafe-200/80 dark:border-cafe-700'
                                            : 'text-cafe-600 dark:text-cafe-400 hover:text-cafe-900'"
                                    >
                                        <MailSendRegular class="w-4 h-4" />
                                        <span>{{ t('exports.email_delivery') }}</span>
                                    </button>
                                </div>

                                <!-- If Email delivery: Email Input or SMTP missing alert -->
                                <div v-if="form.delivery_method === 'email'" class="pt-2 space-y-2">
                                    <div v-if="!smtpAvailable" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-200 flex items-start gap-2">
                                        <AlertRegular class="w-4 h-4 shrink-0 text-amber-600 mt-0.5" />
                                        <div class="space-y-1">
                                            <p>{{ t('exports.smtp_required') }}</p>
                                            <Link :href="route('settings.index')" class="font-bold underline hover:text-amber-950 dark:hover:text-white inline-flex items-center gap-1">
                                                <span>{{ t('exports.configure_smtp') }}</span>
                                                <ExternalLinkRegular class="w-3 h-3" />
                                            </Link>
                                        </div>
                                    </div>

                                    <div v-else class="space-y-1.5">
                                        <CpInput
                                            id="recipient_email"
                                            v-model="form.recipient_email"
                                            type="email"
                                            :label="t('exports.recipient_email')"
                                            :placeholder="t('exports.recipient_placeholder')"
                                            required
                                        />

                                        <button
                                            v-if="selectedStudentObj?.email && form.recipient_email !== selectedStudentObj.email"
                                            type="button"
                                            @click="form.recipient_email = selectedStudentObj.email"
                                            class="text-xs text-accent-600 dark:text-accent-400 hover:underline inline-flex items-center gap-1"
                                        >
                                            <MailRegular class="w-3 h-3" />
                                            <span>{{ t('exports.use_student_email') }} ({{ selectedStudentObj.email }})</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button Area -->
                    <div class="pt-4 border-t border-cafe-100 dark:border-cafe-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-xs text-cafe-500 dark:text-cafe-400">
                            <span v-if="!isFormValid" class="text-state-danger flex items-center gap-1.5 font-medium">
                                <AlertRegular class="w-4 h-4" />
                                <span>{{ t('exports.validation_required') }}</span>
                            </span>
                            <span v-else class="text-state-success flex items-center gap-1.5 font-medium">
                                <CheckCircleRegular class="w-4 h-4" />
                                <span>{{ t('exports.validation_ready', { format: form.format.toUpperCase() }) }}</span>
                            </span>
                        </div>

                        <CpButton
                            type="button"
                            @click="submitExport"
                            :disabled="submitting || !isFormValid"
                            class="min-w-[200px] shadow-sm"
                        >
                            <span v-if="submitting" class="flex items-center justify-center gap-2">
                                <Refresh1Regular class="w-4 h-4 animate-spin" />
                                <span>{{ t('exports.generating') }}</span>
                            </span>
                            <span v-else class="flex items-center justify-center gap-2">
                                <component :is="form.delivery_method === 'email' ? MailSendRegular : FileDownloadRegular" class="w-4 h-4" />
                                <span>{{ form.delivery_method === 'email' ? t('exports.send_email') : `${t('exports.generate')} (${form.format.toUpperCase()})` }}</span>
                            </span>
                        </CpButton>
                    </div>

                </div>
            </div>

            <!-- Export History & Audit Vault -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-cafe-100 dark:bg-surface-dark-2 text-cafe-700 dark:text-cafe-300">
                            <FileDownloadRegular class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="font-serif text-lg font-bold text-cafe-900 dark:text-cafe-100">
                                {{ t('exports.recent_history') }}
                            </h2>
                            <p class="text-xs text-cafe-500 dark:text-cafe-400">
                                {{ exports.length }} {{ t('exports.history_count') }}
                            </p>
                        </div>
                    </div>

                    <!-- Search and Filters Bar -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Search Box -->
                        <div class="relative min-w-[220px]">
                            <SearchRegular class="w-4 h-4 absolute left-3 top-2.5 text-cafe-400 pointer-events-none" />
                            <input
                                v-model="historySearch"
                                type="text"
                                :placeholder="t('exports.search_placeholder')"
                                class="w-full pl-9 pr-3 py-1.5 text-xs bg-white dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-xl text-cafe-800 dark:text-cafe-200 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500"
                            />
                            <button
                                v-if="historySearch"
                                type="button"
                                @click="historySearch = ''"
                                class="absolute right-2.5 top-2 text-cafe-400 hover:text-cafe-600"
                            >
                                <CloseCircleRegular class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <!-- Type Filter -->
                        <select
                            v-model="historyTypeFilter"
                            class="px-3 py-1.5 text-xs bg-white dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-xl text-cafe-700 dark:text-cafe-200 focus:outline-none focus:ring-1 focus:ring-accent-500"
                        >
                            <option value="all">{{ t('exports.filter_all') }} ({{ t('exports.export_type') }})</option>
                            <option value="group">{{ t('exports.type_group') }}</option>
                            <option value="student">{{ t('exports.type_student') }}</option>
                            <option value="period">{{ t('exports.type_period') }}</option>
                        </select>

                        <!-- Format Filter -->
                        <select
                            v-model="historyFormatFilter"
                            class="px-3 py-1.5 text-xs bg-white dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-xl text-cafe-700 dark:text-cafe-200 focus:outline-none focus:ring-1 focus:ring-accent-500"
                        >
                            <option value="all">{{ t('exports.filter_all') }} ({{ t('exports.format') }})</option>
                            <option value="xlsx">Excel (.xlsx)</option>
                            <option value="pdf">PDF (.pdf)</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="json">JSON (.json)</option>
                        </select>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredExports.length === 0"
                    class="rounded-3xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 py-14 px-6 text-center space-y-3"
                >
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-cafe-100 dark:bg-surface-dark-2 text-cafe-400 flex items-center justify-center">
                        <FileDownloadRegular class="w-6 h-6" />
                    </div>
                    <h3 class="text-sm font-bold text-cafe-800 dark:text-cafe-200">
                        {{ t('exports.empty') }}
                    </h3>
                    <p class="text-xs text-cafe-500 dark:text-cafe-400 max-w-sm mx-auto">
                        {{ t('exports.empty_hint') }}
                    </p>
                </div>

                <!-- Enhanced Audit Table -->
                <div v-else class="rounded-3xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-cafe-100 dark:border-cafe-800 bg-cafe-50/50 dark:bg-surface-dark-2/50 text-[11px] font-bold text-cafe-500 dark:text-cafe-400 uppercase tracking-wider">
                                    <th class="py-3 px-4">{{ t('exports.col_format') }}</th>
                                    <th class="py-3 px-4">{{ t('exports.col_type') }}</th>
                                    <th class="py-3 px-4">{{ t('exports.col_context') }}</th>
                                    <th class="py-3 px-4">{{ t('exports.col_template') }}</th>
                                    <th class="py-3 px-4">{{ t('exports.col_delivery') }}</th>
                                    <th class="py-3 px-4">{{ t('exports.col_date') }}</th>
                                    <th class="py-3 px-4 text-right">{{ t('exports.col_action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-cafe-100 dark:divide-cafe-800/60 text-xs">
                                <tr
                                    v-for="exp in filteredExports"
                                    :key="exp.id"
                                    class="hover:bg-cafe-50/80 dark:hover:bg-surface-dark-2/80 transition-colors"
                                >
                                    <!-- Format Pill -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="px-2.5 py-1 rounded-xl font-mono font-bold text-[11px] inline-flex items-center gap-1.5"
                                                :class="getFormatMeta(exp.format).badgeBg"
                                            >
                                                <component :is="getFormatMeta(exp.format).icon" class="w-3.5 h-3.5" />
                                                <span>{{ exp.format.toUpperCase() }}</span>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Type Pill -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-cafe-100 dark:bg-surface-dark-3 text-cafe-700 dark:text-cafe-300">
                                            {{ t('exports.type_' + exp.type) || exp.type }}
                                        </span>
                                    </td>

                                    <!-- Context & Filename -->
                                    <td class="py-3.5 px-4 max-w-xs">
                                        <div class="space-y-0.5">
                                            <p class="font-semibold text-cafe-900 dark:text-cafe-100 truncate" :title="exp.context_label || undefined">
                                                {{ exp.context_label }}
                                            </p>
                                            <p class="font-mono text-[10px] text-cafe-400 truncate" :title="exp.file_name || undefined">
                                                {{ exp.file_name }}
                                            </p>
                                        </div>
                                    </td>

                                    <!-- Template -->
                                    <td class="py-3.5 px-4 whitespace-nowrap text-cafe-600 dark:text-cafe-300">
                                        <span v-if="exp.template_name" class="inline-flex items-center gap-1">
                                            <span>{{ exp.template_name }}</span>
                                        </span>
                                        <span v-else class="text-cafe-400 dark:text-cafe-600">—</span>
                                    </td>

                                    <!-- Delivery Status -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span
                                            v-if="exp.sent_via_email"
                                            class="inline-flex items-center gap-1.5 text-xs text-cafe-600 dark:text-cafe-300"
                                            :title="exp.recipient_email || undefined"
                                        >
                                            <MailSendRegular class="w-3.5 h-3.5 text-accent-600" />
                                            <span class="truncate max-w-[140px]">{{ exp.recipient_email }}</span>
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1.5 text-xs text-cafe-600 dark:text-cafe-300">
                                            <Download2Regular class="w-3.5 h-3.5 text-cafe-400" />
                                            <span>{{ t('exports.direct_download') }}</span>
                                        </span>
                                    </td>

                                    <!-- Date -->
                                    <td class="py-3.5 px-4 whitespace-nowrap text-cafe-500 dark:text-cafe-400">
                                        {{ formatDate(exp.created_at) }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                        <button
                                            v-if="exp.can_download"
                                            type="button"
                                            @click="downloadExport(exp)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-accent-50 hover:bg-accent-100 dark:bg-accent-950/40 dark:hover:bg-accent-900/50 text-accent-700 dark:text-accent-300 border border-accent-200/60 dark:border-accent-800/60 transition-colors shadow-2xs"
                                        >
                                            <Download2Regular class="w-3.5 h-3.5" />
                                            <span>{{ t('exports.redownload') }}</span>
                                        </button>
                                        <span v-else class="text-xs text-cafe-400 dark:text-cafe-600 italic">
                                            {{ t('exports.unavailable') }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
