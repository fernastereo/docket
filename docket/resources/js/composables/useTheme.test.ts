import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

// useTheme guarda el estado a nivel de módulo (un tema por página): cada test
// importa una copia limpia.
async function freshTheme() {
    vi.resetModules();

    return import('@/composables/useTheme');
}

function stubSystemPrefersDark(dark: boolean) {
    vi.stubGlobal(
        'matchMedia',
        vi.fn().mockReturnValue({ matches: dark, addEventListener: vi.fn(), removeEventListener: vi.fn() }),
    );
}

describe('useTheme', () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.classList.remove('dark');
        stubSystemPrefersDark(false);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('dark adds the class to <html> and remembers the choice', async () => {
        const { useTheme } = await freshTheme();

        useTheme().setTheme('dark');

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(localStorage.getItem('theme')).toBe('dark');
    });

    it('light removes the class', async () => {
        document.documentElement.classList.add('dark');
        const { useTheme } = await freshTheme();

        useTheme().setTheme('light');

        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('system follows the operating system preference', async () => {
        stubSystemPrefersDark(true);
        const { useTheme } = await freshTheme();

        useTheme().setTheme('system');

        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('starts from the stored theme', async () => {
        localStorage.setItem('theme', 'dark');
        const { initTheme } = await freshTheme();

        initTheme();

        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('ignores a garbage stored value and falls back to the system theme', async () => {
        localStorage.setItem('theme', 'neon');
        const { useTheme } = await freshTheme();

        expect(useTheme().theme.value).toBe('system');
    });

    it('still applies the theme when storage is blocked', async () => {
        const { useTheme } = await freshTheme();
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('blocked');
        });

        expect(() => useTheme().setTheme('dark')).not.toThrow();
        expect(document.documentElement.classList.contains('dark')).toBe(true);
        vi.restoreAllMocks();
    });
});
