import { ref } from 'vue';

export type Theme = 'light' | 'dark' | 'system';

const STORAGE_KEY = 'theme';
const THEMES: readonly Theme[] = ['light', 'dark', 'system'];

function readStored(): Theme {
    try {
        const value = localStorage.getItem(STORAGE_KEY);
        if (THEMES.includes(value as Theme)) return value as Theme;
    } catch {
        // almacenamiento bloqueado (modo privado, etc.): se usa el del sistema
    }

    return 'system';
}

const theme = ref<Theme>(readStored());

function prefersDark(): boolean {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

/** Pone o quita la clase `dark` en <html>. */
export function applyTheme(value: Theme): void {
    const dark = value === 'dark' || (value === 'system' && prefersDark());
    document.documentElement.classList.toggle('dark', dark);
}

/**
 * Aplica el tema guardado y sigue los cambios del sistema mientras el usuario
 * esté en "sistema". El script de resources/views/app.blade.php hace lo mismo
 * antes de pintar, para que no haya un destello de tema claro.
 */
export function initTheme(): void {
    applyTheme(theme.value);
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (theme.value === 'system') applyTheme('system');
    });
}

export function useTheme() {
    function setTheme(value: Theme): void {
        theme.value = value;
        try {
            localStorage.setItem(STORAGE_KEY, value);
        } catch {
            // sin almacenamiento: el cambio vale solo para esta sesión
        }
        applyTheme(value);
    }

    return { theme, setTheme };
}
