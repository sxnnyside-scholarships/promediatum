import { beforeEach, describe, expect, it } from 'bun:test';
import { useTauri } from '@/composables/useTauri';

describe('useTauri Composable', () => {
    beforeEach(() => {
        // Clean up Tauri globals between tests
        delete (window as Record<string, unknown>).__TAURI_INTERNALS__;
        delete (window as Record<string, unknown>).__TAURI__;
    });

    it('detects when NOT running inside Tauri (browser mode)', () => {
        const { isTauri } = useTauri();
        expect(isTauri.value).toBe(false);
    });

    it('gracefully returns null or false in browser mode without throwing errors', async () => {
        const {
            pickBackupFile,
            pickFolder,
            saveBackupDialog,
            revealInFileManager,
            getSystemInfo,
            getAppPaths,
            pingBackend,
            sendNativeNotification,
        } = useTauri();

        expect(await pickBackupFile()).toBeNull();
        expect(await pickFolder()).toBeNull();
        expect(await saveBackupDialog()).toBeNull();
        expect(await revealInFileManager('/some/path')).toBe(false);
        expect(await getSystemInfo()).toBeNull();
        expect(await getAppPaths()).toBeNull();
        expect(await pingBackend()).toBeNull();
        expect(await sendNativeNotification('Test')).toBe(false);
    });

    it('detects when running inside Tauri desktop runtime', () => {
        (window as Record<string, unknown>).__TAURI_INTERNALS__ = {};
        const { isTauri } = useTauri();
        expect(isTauri.value).toBe(true);
    });
});
