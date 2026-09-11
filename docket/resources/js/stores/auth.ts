import { defineStore } from 'pinia';
import { api } from '@/api/client';

interface AuthUser {
    id: string;
    name: string;
    email: string;
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as AuthUser | null,
        loaded: false,
    }),
    actions: {
        async fetchUser(): Promise<void> {
            try {
                this.user = await api<AuthUser>('/user');
            } catch {
                this.user = null;
            } finally {
                this.loaded = true;
            }
        },
    },
});
