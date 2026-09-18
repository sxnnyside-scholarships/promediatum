<script setup lang="ts">
/**
 * Students Create — Pedagogical Student Registration
 *
 * Fully integrated with the Café Pedagógico Design System:
 * rounded-2xl cards, restful gradient backdrop, initials pseudo-avatar preview,
 * amber lightbulb tips panel, and multi-group assignment grid.
 */

import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AddCircleRegular,
    BulbRegular,
    CheckCircleRegular,
    GroupRegular,
    LeftSmallRegular,
    MailRegular,
    NotebookRegular,
    PhoneRegular,
    User4Regular,
} from '@mingcute/vue/core-regular';
import { computed } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface GroupItem {
    id: number;
    name: string;
    subject?: string;
    period?: {
        id: number;
        name: string;
        is_active?: boolean;
    };
}

const props = defineProps<{
    availableGroups?: GroupItem[];
}>();

const { t } = useTranslations();

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    guardian_name: '',
    notes: '',
    group_ids: [] as number[],
});

// Live avatar preview
const previewStudent = computed(() => ({
    first_name: form.first_name,
    last_name: form.last_name,
    full_name: `${form.first_name} ${form.last_name}`.trim() || 'Nuevo Estudiante',
}));

function toggleGroup(groupId: number) {
    const idx = form.group_ids.indexOf(groupId);
    if (idx >= 0) {
        form.group_ids.splice(idx, 1);
    } else {
        form.group_ids.push(groupId);
    }
}

function selectAllGroups() {
    if (!props.availableGroups) return;
    form.group_ids = props.availableGroups.map((g) => g.id);
}

function deselectAllGroups() {
    form.group_ids = [];
}

function submit() {
    form.post(route('students.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('students.create')" />

        <div class="space-y-6 max-w-5xl">
            <!-- Back button -->
            <div>
                <Link
                    :href="route('students.index')"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-cafe-600 dark:text-cafe-400 hover:text-cafe-900 dark:hover:text-cafe-100 transition-colors"
                >
                    <LeftSmallRegular class="w-4 h-4" />
                    <span>{{ t('students.back') }}</span>
                </Link>
            </div>

            <!-- Header card with restful pedagogical gradient -->
            <div class="rounded-2xl border border-cafe-200/75 dark:border-cafe-800/80 bg-gradient-to-br from-white via-cafe-50/70 to-amber-50/20 dark:from-surface-dark-1 dark:via-surface-dark-2/50 dark:to-surface-dark-3/30 p-5 sm:p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <StudentAvatar :student="previewStudent" size="xl" />
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-cafe-900 dark:text-cafe-50 tracking-tight">
                            {{ previewStudent.full_name }}
                        </h1>
                        <p class="text-xs sm:text-sm text-cafe-600 dark:text-cafe-300 font-sans mt-0.5 leading-relaxed">
                            {{ t('students.initials_avatar_note') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Main Form Grid -->
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-10 gap-6">
                <!-- Form Fields (Col 7) -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Identity Section -->
                    <div class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 p-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 border-b border-cafe-100 dark:border-cafe-800 pb-3">
                            <User4Regular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                            <h2 class="text-xs font-bold uppercase tracking-wider text-cafe-700 dark:text-cafe-200">
                                {{ t('students.name') }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <CpInput
                                id="first_name"
                                v-model="form.first_name"
                                :label="t('field.first_name')"
                                :error="form.errors.first_name"
                                required
                                autofocus
                            />

                            <CpInput
                                id="last_name"
                                v-model="form.last_name"
                                :label="t('field.last_name')"
                                :error="form.errors.last_name"
                                required
                            />
                        </div>
                    </div>

                    <!-- Multi-Group Assignment Section -->
                    <div class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 p-6 shadow-sm space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-cafe-100 dark:border-cafe-800 pb-3">
                            <div class="flex items-center gap-2">
                                <GroupRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                                <div>
                                    <h2 class="text-xs font-bold uppercase tracking-wider text-cafe-700 dark:text-cafe-200">
                                        {{ t('students.assign_groups') }}
                                    </h2>
                                    <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                        {{ t('students.assign_groups_hint') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Quick toggle buttons -->
                            <div v-if="availableGroups?.length" class="flex items-center gap-2 text-xs">
                                <button
                                    type="button"
                                    class="text-accent-600 dark:text-accent-400 hover:underline font-medium"
                                    @click="selectAllGroups"
                                >
                                    {{ t('students.select_all_groups') }}
                                </button>
                                <span class="text-cafe-300">|</span>
                                <button
                                    type="button"
                                    class="text-cafe-500 hover:text-cafe-700 dark:text-cafe-400 hover:underline font-medium"
                                    @click="deselectAllGroups"
                                >
                                    {{ t('students.deselect_all') }}
                                </button>
                            </div>
                        </div>

                        <!-- Groups selection checklist -->
                        <div v-if="availableGroups?.length" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div
                                v-for="grp in availableGroups"
                                :key="grp.id"
                                :class="[
                                    'p-3 rounded-xl border cursor-pointer transition-all flex items-center justify-between select-none',
                                    form.group_ids.includes(grp.id)
                                        ? 'bg-accent-50/70 dark:bg-accent-950/30 border-accent-400 dark:border-accent-700/70'
                                        : 'bg-cafe-50/60 dark:bg-surface-dark-2 border-cafe-200/80 dark:border-cafe-700/80 hover:border-cafe-300',
                                ]"
                                @click="toggleGroup(grp.id)"
                            >
                                <div class="min-w-0 pr-2">
                                    <p class="font-medium text-sm text-cafe-800 dark:text-cafe-100 truncate">
                                        {{ grp.name }}
                                    </p>
                                    <p v-if="grp.period" class="text-xs text-cafe-500 dark:text-cafe-400 truncate">
                                        {{ grp.period.name }}
                                    </p>
                                </div>

                                <div
                                    :class="[
                                        'w-5 h-5 rounded-lg flex items-center justify-center border transition-colors shrink-0',
                                        form.group_ids.includes(grp.id)
                                            ? 'bg-accent-600 border-accent-600 text-white'
                                            : 'border-cafe-300 dark:border-cafe-600 bg-white dark:bg-surface-dark-1',
                                    ]"
                                >
                                    <CheckCircleRegular v-if="form.group_ids.includes(grp.id)" class="w-3.5 h-3.5" />
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-xs text-cafe-400 dark:text-cafe-500 py-2">
                            {{ t('students.no_active_groups') }}
                        </div>
                        <p v-if="form.errors.group_ids" class="cp-error text-xs">{{ form.errors.group_ids }}</p>
                    </div>

                    <!-- Contact & Guardian Section -->
                    <div class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 p-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 border-b border-cafe-100 dark:border-cafe-800 pb-3">
                            <MailRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                            <div>
                                <h2 class="text-xs font-bold uppercase tracking-wider text-cafe-700 dark:text-cafe-200">
                                    {{ t('students.contact_info') }}
                                </h2>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                    {{ t('students.contact_subtitle') }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label for="guardian_name" class="cp-label block text-xs font-medium text-cafe-700 dark:text-cafe-200 mb-1">
                                    {{ t('students.guardian_name') }}
                                </label>
                                <input
                                    id="guardian_name"
                                    v-model="form.guardian_name"
                                    type="text"
                                    :placeholder="t('students.guardian_placeholder')"
                                    class="w-full px-3 py-2 text-sm bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500"
                                />
                                <p v-if="form.errors.guardian_name" class="cp-error text-xs mt-1">{{ form.errors.guardian_name }}</p>
                            </div>

                            <div>
                                <label for="email" class="cp-label block text-xs font-medium text-cafe-700 dark:text-cafe-200 mb-1">
                                    {{ t('students.email') }}
                                </label>
                                <div class="relative">
                                    <MailRegular class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-cafe-400 pointer-events-none" />
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        :placeholder="t('students.email_placeholder')"
                                        class="w-full pl-9 pr-3 py-2 text-sm bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500"
                                    />
                                </div>
                                <p v-if="form.errors.email" class="cp-error text-xs mt-1">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label for="phone" class="cp-label block text-xs font-medium text-cafe-700 dark:text-cafe-200 mb-1">
                                    {{ t('students.phone') }}
                                </label>
                                <div class="relative">
                                    <PhoneRegular class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-cafe-400 pointer-events-none" />
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="text"
                                        :placeholder="t('students.phone_placeholder')"
                                        class="w-full pl-9 pr-3 py-2 text-sm bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500"
                                    />
                                </div>
                                <p v-if="form.errors.phone" class="cp-error text-xs mt-1">{{ form.errors.phone }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Teacher Pedagogical Notes Section -->
                    <div class="rounded-2xl border border-cafe-200 dark:border-cafe-800 bg-white dark:bg-surface-dark-1 p-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 border-b border-cafe-100 dark:border-cafe-800 pb-3">
                            <NotebookRegular class="w-4 h-4 text-accent-600 dark:text-accent-400" />
                            <div>
                                <h2 class="text-xs font-bold uppercase tracking-wider text-cafe-700 dark:text-cafe-200">
                                    {{ t('students.notes') }}
                                </h2>
                                <p class="text-xs text-cafe-500 dark:text-cafe-400 mt-0.5">
                                    {{ t('students.notes_subtitle') }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                :placeholder="t('students.notes_placeholder')"
                                class="w-full px-3 py-2 text-sm bg-cafe-50/60 dark:bg-surface-dark-2 border border-cafe-200 dark:border-cafe-700 rounded-lg text-cafe-900 dark:text-cafe-100 placeholder-cafe-400 focus:outline-none focus:ring-1 focus:ring-accent-500"
                            />
                            <p v-if="form.errors.notes" class="cp-error text-xs mt-1">{{ form.errors.notes }}</p>
                        </div>
                    </div>

                    <!-- Submit action button -->
                    <div class="pt-2">
                        <CpButton type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 text-sm shadow-sm">
                            <AddCircleRegular class="w-4 h-4" />
                            <span>{{ t('students.submit') }}</span>
                        </CpButton>
                    </div>
                </div>

                <!-- Teacher Tips Context Panel with BulbRegular (30%) -->
                <aside class="lg:col-span-3 space-y-4">
                    <div class="rounded-2xl border border-amber-200/80 dark:border-amber-900/60 bg-gradient-to-b from-amber-50/70 to-white dark:from-surface-dark-2 dark:to-surface-dark-1 p-5 shadow-sm space-y-3.5">
                        <div class="flex items-center gap-2.5 text-amber-700 dark:text-amber-400">
                            <div class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/70">
                                <BulbRegular class="w-4 h-4" />
                            </div>
                            <h3 class="text-xs font-bold uppercase tracking-wider">
                                {{ t('students.tips_title') }}
                            </h3>
                        </div>
                        <ul class="space-y-3 text-xs text-cafe-600 dark:text-cafe-300 leading-relaxed">
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0" />
                                <span>{{ t('students.tip_1') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0" />
                                <span>{{ t('students.tip_2') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0" />
                                <span>{{ t('students.tip_3') }}</span>
                            </li>
                        </ul>
                    </div>
                </aside>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
