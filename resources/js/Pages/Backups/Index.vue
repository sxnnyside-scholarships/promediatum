<script setup>
/**
 * Backups Index — /backups
 * Desktop backup management: create, restore, validate, delete, download backups.
 * Uses the BackupService (.pdbk encrypted format).
 */

import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CpButton from '@/Components/CpButton.vue';
import CpInput from '@/Components/CpInput.vue';
import { useToast } from '@/composables/useToast';
import { useTranslations } from '@/composables/useTranslations';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    backups: { type: Array, default: () => [] },
});

const { t } = useTranslations();
const toast = useToast();
const page = usePage();

const createPassword = ref('');
const restorePassword = ref('');
const selectedBackup = ref(null);
const isCreating = ref(false);
const isRestoring = ref(false);

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / k ** i).toFixed(1))} ${sizes[i]}`;
}

function createBackup() {
    isCreating.value = true;
    router.post(
        route('backup.store'),
        {
            password: createPassword.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                createPassword.value = '';
                toast.success(t('backup.created_successfully'));
            },
            onError: () => toast.error(t('backup.creation_failed', { error: 'Unknown' })),
            onFinish: () => (isCreating.value = false),
        },
    );
}

function restoreBackup(backup) {
    if (!confirm(t('backup.confirm_restore'))) return;
    isRestoring.value = true;
    router.post(
        route('backup.restore'),
        {
            backup_path: backup.path,
            password: restorePassword.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                restorePassword.value = '';
                toast.success(t('backup.restored_successfully'));
            },
            onError: () => toast.error(t('backup.restore_failed', { error: 'Unknown' })),
            onFinish: () => (isRestoring.value = false),
        },
    );
}

function deleteBackup(backup) {
    if (!confirm(t('backup.confirm_delete'))) return;
    router.delete(route('backup.destroy'), {
        data: { backup_path: backup.path },
        preserveScroll: true,
        onSuccess: () => toast.success(t('backup.deleted_successfully')),
    });
}

function downloadBackup(backup) {
    window.location.href = `${route('backup.download')}?backup_path=${encodeURIComponent(backup.path)}`;
}

function pruneBackups() {
    router.post(
        route('backup.prune'),
        { keep: 5 },
        {
            preserveScroll: true,
            onSuccess: () => toast.success(t('backup.pruned_successfully', { count: '' })),
        },
    );
}
</script>

<template>
    <Head :title="t('backup.title')" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-8 px-4 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-[var(--cp-text)]">{{ t('backup.title') }}</h1>
            </div>

            <!-- Create Backup Card -->
            <div class="cp-card p-6 space-y-4">
                <h2 class="text-lg font-semibold text-[var(--cp-text)]">{{ t('backup.create') }}</h2>

                <div class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-[var(--cp-text-secondary)] mb-1">
                            {{ t('backup.password') }}
                        </label>
                        <CpInput
                            v-model="createPassword"
                            type="password"
                            :placeholder="t('backup.password_hint')"
                            class="w-full"
                        />
                    </div>
                    <CpButton
                        variant="primary"
                        :disabled="isCreating"
                        @click="createBackup"
                    >
                        {{ isCreating ? '...' : t('backup.create') }}
                    </CpButton>
                </div>
            </div>

            <!-- Backup List -->
            <div class="cp-card overflow-hidden">
                <div v-if="props.backups.length === 0" class="p-8 text-center text-[var(--cp-text-secondary)]">
                    {{ t('backup.empty') }}
                </div>

                <table v-else class="w-full">
                    <thead>
                        <tr class="border-b border-[var(--cp-border)]">
                            <th class="text-left px-4 py-3 text-sm font-medium text-[var(--cp-text-secondary)]">
                                {{ t('backup.col_filename') }}
                            </th>
                            <th class="text-left px-4 py-3 text-sm font-medium text-[var(--cp-text-secondary)]">
                                {{ t('backup.col_size') }}
                            </th>
                            <th class="text-left px-4 py-3 text-sm font-medium text-[var(--cp-text-secondary)]">
                                {{ t('backup.col_date') }}
                            </th>
                            <th class="text-right px-4 py-3 text-sm font-medium text-[var(--cp-text-secondary)]">
                                {{ t('backup.col_actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="backup in props.backups"
                            :key="backup.filename"
                            class="border-b border-[var(--cp-border)] last:border-0 hover:bg-[var(--cp-surface-hover)] transition-colors"
                        >
                            <td class="px-4 py-3 text-sm text-[var(--cp-text)] font-mono">
                                {{ backup.filename }}
                            </td>
                            <td class="px-4 py-3 text-sm text-[var(--cp-text-secondary)]">
                                {{ formatBytes(backup.size) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-[var(--cp-text-secondary)]">
                                {{ new Date(backup.created_at).toLocaleString() }}
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <CpButton variant="ghost" size="sm" @click="downloadBackup(backup)">
                                    {{ t('backup.download') }}
                                </CpButton>
                                <CpButton variant="ghost" size="sm" @click="restoreBackup(backup)">
                                    {{ t('backup.restore') }}
                                </CpButton>
                                <CpButton variant="ghost" size="sm" class="text-red-500" @click="deleteBackup(backup)">
                                    {{ t('backup.delete') }}
                                </CpButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Restore Password (for restoring encrypted backups) -->
            <div v-if="props.backups.length > 0" class="cp-card p-6 space-y-4">
                <h2 class="text-lg font-semibold text-[var(--cp-text)]">{{ t('backup.restore') }}</h2>
                <div class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-[var(--cp-text-secondary)] mb-1">
                            {{ t('backup.password') }}
                        </label>
                        <CpInput
                            v-model="restorePassword"
                            type="password"
                            :placeholder="t('backup.password_hint')"
                            class="w-full"
                        />
                    </div>
                    <CpButton variant="secondary" @click="pruneBackups">
                        {{ t('backup.prune') }}
                    </CpButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
