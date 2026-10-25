import { defineStore } from 'pinia';
import { api, ApiError } from '@/api/client';
import type { MfaSetup, PlatformUser } from '@/features/platform/types';

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

        /** Genera el secreto y el QR de activación. No activa MFA todavía. */
        async startMfaSetup(): Promise<MfaSetup> {
            return (await api<{ data: MfaSetup }>('/platform/mfa/setup', { method: 'POST' })).data;
        },

        /** Activa MFA con un primer código. Devuelve los códigos de recuperación (se muestran una sola vez). */
        async confirmMfa(code: string): Promise<string[]> {
            const response = await api<{ data: { recovery_codes: string[]; user: PlatformUser } }>('/platform/mfa/confirm', {
                method: 'POST',
                body: JSON.stringify({ code }),
            });
            this.user = response.data.user;

            return response.data.recovery_codes;
        },

        /** Segundo paso del login: código de la app o de recuperación. */
        async verifyMfa(code: string): Promise<void> {
            this.user = (
                await api<{ data: PlatformUser }>('/platform/mfa/verify', {
                    method: 'POST',
                    body: JSON.stringify({ code }),
                })
            ).data;
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
