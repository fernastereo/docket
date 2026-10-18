import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { ApiError } from '@/api/client';
import { useTenantsStore } from '@/features/platform/stores/tenants';
import type { Tenant } from '@/features/platform/types';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

const tenant = (overrides: Partial<Tenant> = {}): Tenant => ({
    id: 't1',
    name: 'Curaduría Uno',
    host: 'cuuno.docket.test',
    status: 'demo',
    failure_reason: null,
    provisioned_at: null,
    created_at: '2026-10-18T10:00:00+00:00',
    ...overrides,
});

describe('tenants store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        api.mockReset();
    });

    it('loads the list and the base domain', async () => {
        api.mockResolvedValue({ data: [tenant()], meta: { base_domain: 'docket.test' } });
        const tenants = useTenantsStore();

        await tenants.load();

        expect(tenants.items).toHaveLength(1);
        expect(tenants.baseDomain).toBe('docket.test');
        expect(tenants.loading).toBe(false);
    });

    it('stops loading when the request fails', async () => {
        api.mockRejectedValue(new ApiError('Server error', 500));
        const tenants = useTenantsStore();

        await expect(tenants.load()).rejects.toBeInstanceOf(ApiError);

        expect(tenants.loading).toBe(false);
    });

    it('a created tenant goes to the top of the list', async () => {
        const tenants = useTenantsStore();
        tenants.items = [tenant({ id: 'old' })];
        api.mockResolvedValue({ data: tenant({ id: 'new' }) });

        await tenants.create({ name: 'Curaduría Dos', subdomain: 'cudos', active: false });

        expect(tenants.items.map((t) => t.id)).toEqual(['new', 'old']);
        expect(api).toHaveBeenCalledWith('/platform/tenants', {
            method: 'POST',
            body: JSON.stringify({ name: 'Curaduría Dos', subdomain: 'cudos', active: false }),
        });
    });

    it('a rejected creation does not touch the list', async () => {
        const tenants = useTenantsStore();
        tenants.items = [tenant()];
        api.mockRejectedValue(new ApiError('Invalid', 422, { subdomain: ['Taken'] }));

        await expect(tenants.create({ name: 'X', subdomain: 'cuuno', active: false })).rejects.toBeInstanceOf(ApiError);

        expect(tenants.items).toHaveLength(1);
    });
});
