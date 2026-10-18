import { defineStore } from 'pinia';
import { api, ApiError } from '@/api/client';
import type { PlatformUser } from '@/features/platform/types';

/**
 * Sesión del usuario de plataforma. La cookie de sesión es httpOnly (el
 * navegador la maneja); acá solo se guarda quién es, nunca credenciales.
 */
export const usePlatformAuthStore = defineStore('platformAuth', {
    state: () => ({
        user: null as PlatformUser | null,
        loaded: false,
    }),
    actions: {
        async fetchUser(): Promise<void> {
            try {
                this.user = (await api<{ data: PlatformUser }>('/platform/me')).data;
            } catch (error) {
                if (!(error instanceof ApiError)) throw error;
                this.user = null;
            } finally {
                this.loaded = true;
            }
        },

        /** Lanza ApiError (422 credenciales, 429 bloqueo) para que la pantalla lo muestre. */
        async login(email: string, password: string): Promise<void> {
            const response = await api<{ data: PlatformUser }>('/platform/login', {
                method: 'POST',
                body: JSON.stringify({ email, password }),
            });
            this.user = response.data;
            this.loaded = true;
        },

        async logout(): Promise<void> {
            try {
                await api<void>('/platform/logout', { method: 'POST' });
            } finally {
                this.clear();
            }
        },

        /** Olvida la sesión localmente (logout, o 401 por sesión vencida). */
        clear(): void {
            this.user = null;
            this.loaded = true;
        },
    },
});
