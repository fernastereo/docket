import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { ApiError } from '@/api/client';
import TenantsPage from '@/features/platform/pages/TenantsPage.vue';
import type { Tenant } from '@/features/platform/types';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));
vi.mock('vue-sonner', () => ({ toast: { success: vi.fn() } }));

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

function list(items: Tenant[]) {
    api.mockResolvedValueOnce({ data: items, meta: { base_domain: 'docket.test' } });
}

async function mountPage() {
    setActivePinia(createPinia());
    const wrapper = mount(TenantsPage, { attachTo: document.body, global: { stubs: { RouterLink: true } } });
    await flushPromises();

    return wrapper;
}

describe('TenantsPage', () => {
    // Con llaves a propósito: mockReset() devuelve el mock y vitest lo ejecutaría como limpieza.
    beforeEach(() => {
        api.mockReset();
        document.body.innerHTML = '';
    });

    it('invites the first curaduría when there are none', async () => {
        list([]);

        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('Todavía no hay curadurías');
        expect(wrapper.text()).toContain('Nueva curaduría');
    });

    it('lists each curaduría with its address and status', async () => {
        list([tenant(), tenant({ id: 't2', name: 'Curaduría Dos', host: 'cudos.docket.test', status: 'active' })]);

        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('Curaduría Uno');
        expect(wrapper.text()).toContain('Demo');
        expect(wrapper.text()).toContain('Activa');
        expect(wrapper.find('a[href^="https://cuuno.docket.test"]').exists()).toBe(true);
    });

    it('shows why a failed curaduría failed', async () => {
        list([tenant({ status: 'failed', failure_reason: 'database already exists' })]);

        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('Fallida');
        expect(wrapper.text()).toContain('database already exists');
    });

    it('says so when the list cannot be loaded', async () => {
        api.mockRejectedValueOnce(new ApiError('Server error', 500));

        const wrapper = await mountPage();

        expect(wrapper.get('[role="alert"]').text()).toContain('No se pudo cargar la lista');
    });

    it('creating from the dialog shows the field errors and keeps it open', async () => {
        list([]);
        const wrapper = await mountPage();
        await wrapper.findAll('button').find((b) => b.text().includes('Nueva curaduría'))!.trigger('click');
        await flushPromises();

        const dialog = document.body.querySelector('[role="dialog"]') as HTMLElement;
        expect(dialog).not.toBeNull();

        api.mockRejectedValueOnce(new ApiError('Invalid', 422, { subdomain: ['That subdomain is reserved.'] }));
        const name = dialog.querySelector('#name') as HTMLInputElement;
        const subdomain = dialog.querySelector('#subdomain') as HTMLInputElement;
        name.value = 'Curaduría Uno';
        name.dispatchEvent(new Event('input'));
        subdomain.value = 'admin';
        subdomain.dispatchEvent(new Event('input'));
        dialog.querySelector('form')!.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(document.body.textContent).toContain('That subdomain is reserved.');
        expect(document.body.querySelector('[role="dialog"]')).not.toBeNull();
    });
});
