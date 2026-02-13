import { ref, onMounted } from 'vue';

/**
 * Theme composable — supports 'system', 'light', 'dark' modes.
 *
 * - `mode`   — reactive ref: 'system' | 'light' | 'dark'
 * - `isDark` — reactive ref: resolved boolean (true if dark is active)
 * - `setMode(m)` — set mode explicitly
 * - `toggle()` — cycle: light → dark → light (legacy compat)
 */
export function useTheme() {
    const isDark = ref(false);
    const mode = ref('system');

    function applyResolved(dark) {
        isDark.value = dark;
        if (dark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    function resolve(m) {
        if (m === 'dark') return true;
        if (m === 'light') return false;
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function setMode(m) {
        mode.value = m;
        localStorage.setItem('theme', m);
        applyResolved(resolve(m));
    }

    function toggle() {
        setMode(isDark.value ? 'light' : 'dark');
    }

    onMounted(() => {
        const stored = localStorage.getItem('theme');
        if (stored === 'dark' || stored === 'light' || stored === 'system') {
            mode.value = stored;
        } else {
            mode.value = 'system';
        }
        applyResolved(resolve(mode.value));

        // Listen for OS preference changes when in system mode
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (mode.value === 'system') {
                applyResolved(e.matches);
            }
        });
    });

    return { isDark, mode, setMode, toggle };
}
