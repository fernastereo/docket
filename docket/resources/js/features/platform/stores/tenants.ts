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
                const response = await api<{ data: Tenant[]; meta: { base_domain: string } }>('/platform/tenants');
                this.items = response.data;
                this.baseDomain = response.meta.base_domain;
            } finally {
                this.loading = false;
            }
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
    },
});
