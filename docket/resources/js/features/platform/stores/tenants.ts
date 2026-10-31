import { defineStore } from 'pinia';
import { api } from '@/api/client';
import type { NewTenant, Tenant } from '@/features/platform/types';

export const useTenantsStore = defineStore('platformTenants', {
    state: () => ({
        items: [] as Tenant[],
        baseDomain: '' as string,
        loading: false,
    }),
    actions: {
        async load(): Promise<void> {
            this.loading = true;
            try {
                await this.refresh();
            } finally {
                this.loading = false;
            }
        },

        /** Vuelve a leer la lista sin el estado de carga (la tabla no parpadea). */
        async refresh(): Promise<void> {
            const response = await api<{ data: Tenant[]; meta: { base_domain: string } }>('/platform/tenants');
            this.items = response.data;
            this.baseDomain = response.meta.base_domain;
        },

        /** Lanza ApiError (422 con `errors` por campo, 500 si el alta falló). */
        async create(input: NewTenant): Promise<Tenant> {
            const response = await api<{ data: Tenant }>('/platform/tenants', {
                method: 'POST',
                body: JSON.stringify(input),
            });
            this.items.unshift(response.data);

            return response.data;
        },

        /**
         * Envía al administrador su invitación (si no tiene contraseña) o un enlace de
         * recuperación (si ya la tiene). Lanza ApiError (422: sin administrador, cuenta suspendida…).
         */
        async sendAdminLink(id: string): Promise<void> {
            await api(`/platform/tenants/${id}/admin/link`, { method: 'POST' });
            await this.refresh();
        },

        /** Fija la contraseña del administrador. Lanza ApiError (422 con `errors.password`). */
        async setAdminPassword(id: string, password: string, passwordConfirmation: string): Promise<void> {
            await api(`/platform/tenants/${id}/admin/password`, {
                method: 'PUT',
                body: JSON.stringify({ password, password_confirmation: passwordConfirmation }),
            });
            await this.refresh();
        },

        /** Da su administrador a una curaduría que no lo tiene y le envía la invitación. Lanza ApiError (422 con `errors` por campo). */
        async assignAdmin(id: string, name: string, email: string): Promise<void> {
            await api(`/platform/tenants/${id}/admin`, {
                method: 'POST',
                body: JSON.stringify({ admin_name: name, admin_email: email }),
            });
            await this.refresh();
        },

        /** Reinicia la verificación en dos pasos del administrador. Lanza ApiError (422: sin administrador, curaduría no lista). */
        async resetAdminMfa(id: string): Promise<void> {
            await api(`/platform/tenants/${id}/admin/mfa`, { method: 'DELETE' });
            await this.refresh();
        },
    },
});
