import { invoke } from '@tauri-apps/api/core';
import { open as openDialog, save as saveDialog } from '@tauri-apps/plugin-dialog';
import {
    isPermissionGranted,
    requestPermission,
    sendNotification,
} from '@tauri-apps/plugin-notification';
import { computed } from 'vue';

export interface SystemInfo {
    os: string;
    arch: string;
    app_version: string;
    platform: string;
}

export interface AppPaths {
    base_dir: string;
    database_path: string;
    backups_dir: string;
    exports_dir: string;
    logs_dir: string;
}

export interface BackendHealth {
    ok: boolean;
    latency_ms: number;
    port: number;
    host: string;
}

/**
 * Hook to interface seamlessly with native Tauri v2 desktop capabilities.
 * Safe to use in both browser and native desktop runtime (with graceful fallbacks).
 */
export function useTauri() {
    const isTauri = computed<boolean>(() => {
        if (typeof window === 'undefined') return false;
        return '__TAURI_INTERNALS__' in window || '__TAURI__' in window;
    });

    /**
     * Open native file picker specifically filtered for .pdbk backup archives.
     */
    async function pickBackupFile(): Promise<string | null> {
        if (!isTauri.value) return null;
        try {
            const selected = await openDialog({
                title: 'Seleccionar Copia de Seguridad (.pdbk)',
                multiple: false,
                directory: false,
                filters: [
                    {
                        name: 'Promediatum Backup (.pdbk)',
                        extensions: ['pdbk'],
                    },
                ],
            });
            return typeof selected === 'string' ? selected : null;
        } catch (error) {
            console.error('[Tauri] Failed to open native file dialog:', error);
            return null;
        }
    }

    /**
     * Open native directory picker dialog.
     */
    async function pickFolder(title = 'Seleccionar Carpeta'): Promise<string | null> {
        if (!isTauri.value) return null;
        try {
            const selected = await openDialog({
                title,
                directory: true,
                multiple: false,
            });
            return typeof selected === 'string' ? selected : null;
        } catch (error) {
            console.error('[Tauri] Failed to pick directory:', error);
            return null;
        }
    }

    /**
     * Open native save dialog for backup or export files.
     */
    async function saveBackupDialog(
        defaultPath = 'promediatum_backup.pdbk',
    ): Promise<string | null> {
        if (!isTauri.value) return null;
        try {
            const path = await saveDialog({
                title: 'Guardar Respaldo (.pdbk)',
                defaultPath,
                filters: [
                    {
                        name: 'Promediatum Backup (.pdbk)',
                        extensions: ['pdbk'],
                    },
                ],
            });
            return path;
        } catch (error) {
            console.error('[Tauri] Failed to open save dialog:', error);
            return null;
        }
    }

    /**
     * Reveal given file or folder in Finder (macOS) or Explorer (Windows).
     */
    async function revealInFileManager(path: string): Promise<boolean> {
        if (!isTauri.value) return false;
        try {
            return await invoke<boolean>('open_path_in_file_manager', { path });
        } catch (error) {
            console.error('[Tauri] Failed to reveal path in file manager:', error);
            return false;
        }
    }

    /**
     * Query native operating system and application metadata via Rust IPC.
     */
    async function getSystemInfo(): Promise<SystemInfo | null> {
        if (!isTauri.value) return null;
        try {
            return await invoke<SystemInfo>('get_system_info');
        } catch (error) {
            console.error('[Tauri] Failed to get system info:', error);
            return null;
        }
    }

    /**
     * Query canonical storage paths via Rust IPC.
     */
    async function getAppPaths(): Promise<AppPaths | null> {
        if (!isTauri.value) return null;
        try {
            return await invoke<AppPaths>('get_app_paths');
        } catch (error) {
            console.error('[Tauri] Failed to get app paths:', error);
            return null;
        }
    }

    /**
     * Ping the local PHP backend server via TCP from Rust.
     */
    async function pingBackend(host?: string, port?: number): Promise<BackendHealth | null> {
        if (!isTauri.value) return null;
        try {
            return await invoke<BackendHealth>('ping_backend', { host, port });
        } catch (error) {
            console.error('[Tauri] Failed to ping backend:', error);
            return null;
        }
    }

    /**
     * Send OS native notification (macOS Notification Center / Windows Action Center).
     */
    async function sendNativeNotification(title: string, body?: string): Promise<boolean> {
        if (!isTauri.value) return false;
        try {
            let hasPermission = await isPermissionGranted();
            if (!hasPermission) {
                const permission = await requestPermission();
                hasPermission = permission === 'granted';
            }
            if (hasPermission) {
                sendNotification({ title, body });
                return true;
            }
            return false;
        } catch (error) {
            console.error('[Tauri] Failed to send native notification:', error);
            return false;
        }
    }

    return {
        isTauri,
        pickBackupFile,
        pickFolder,
        saveBackupDialog,
        revealInFileManager,
        getSystemInfo,
        getAppPaths,
        pingBackend,
        sendNativeNotification,
    };
}
