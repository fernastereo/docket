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
const toast = vi.hoisted(() => ({ success: vi.fn(), error: vi.fn() }));
vi.mock('vue-sonner', () => ({ toast }));

const tenant = (overrides: Partial<Tenant> = {}): Tenant => ({
    id: 't1',
    name: 'Curaduría Uno',
    host: 'cuuno.docket.test',
    status: 'demo',
    admin: { name: 'Ana Pérez', email: 'ana@cuuno.test', invitation_pending: false },
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
        toast.success.mockReset();
        toast.error.mockReset();
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

    it('creates the curaduría together with its first administrator', async () => {
        list([]);
        const wrapper = await mountPage();
        await wrapper.findAll('button').find((b) => b.text().includes('Nueva curaduría'))!.trigger('click');
        await flushPromises();
        const dialog = document.body.querySelector('[role="dialog"]') as HTMLElement;

        const type = (selector: string, value: string) => {
            const input = dialog.querySelector(selector) as HTMLInputElement;
            input.value = value;
            input.dispatchEvent(new Event('input'));
        };
        type('#name', 'Curaduría Uno');
        type('#subdomain', 'cuuno');
        type('#admin_name', 'Ana Pérez');
        type('#admin_email', 'ana@cuuno.test');
        await flushPromises(); // el Input emite su valor en el siguiente tick
        api.mockResolvedValueOnce({ data: tenant() });
        dialog.querySelector('form')!.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(api).toHaveBeenLastCalledWith(
            '/platform/tenants',
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify({ name: 'Curaduría Uno', subdomain: 'cuuno', active: false, admin_name: 'Ana Pérez', admin_email: 'ana@cuuno.test' }),
            }),
        );
        expect(document.body.querySelector('[role="dialog"]')).toBeNull();
    });

    it('shows the first administrator errors next to their fields', async () => {
        list([]);
        const wrapper = await mountPage();
        await wrapper.findAll('button').find((b) => b.text().includes('Nueva curaduría'))!.trigger('click');
        await flushPromises();
        const dialog = document.body.querySelector('[role="dialog"]') as HTMLElement;

        api.mockRejectedValueOnce(new ApiError('Invalid', 422, { admin_email: ['The admin email must be a valid email address.'] }));
        dialog.querySelector('form')!.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(document.body.textContent).toContain('The admin email must be a valid email address.');
    });

    it('shows the administrator of each curaduría, or a dash when there is none', async () => {
        list([tenant(), tenant({ id: 't2', name: 'Curaduría Dos', status: 'failed', admin: null })]);

        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('Ana Pérez');
        expect(wrapper.text()).toContain('ana@cuuno.test');
        expect(wrapper.findAll('tbody tr')[1].text()).toContain('—');
    });

    it('names the link action after what it will send', async () => {
        list([
            tenant({ id: 'done' }),
            tenant({ id: 'pending', name: 'Curaduría Pendiente', admin: { name: 'Beto Ruiz', email: 'beto@cupend.test', invitation_pending: true } }),
        ]);

        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('Invitación pendiente');
        expect(wrapper.findAll('button[aria-label^="Reenviar invitación"]')).toHaveLength(1);
        expect(wrapper.findAll('button[aria-label^="Enviar enlace de recuperación"]')).toHaveLength(1);
    });

    it('sends the link and says where it went', async () => {
        list([tenant({ id: 'pending', admin: { name: 'Beto Ruiz', email: 'beto@cupend.test', invitation_pending: true } })]);
        const wrapper = await mountPage();
        api.mockResolvedValueOnce(undefined).mockResolvedValueOnce({ data: [], meta: { base_domain: 'docket.test' } });

        await wrapper.get('button[aria-label^="Reenviar invitación"]').trigger('click');
        await flushPromises();

        expect(api).toHaveBeenNthCalledWith(2, '/platform/tenants/pending/admin/link', expect.objectContaining({ method: 'POST' }));
        expect(toast.success).toHaveBeenCalledWith('Invitación enviada', expect.objectContaining({ description: expect.stringContaining('beto@cupend.test') }));
    });

    it('explains why the link could not be sent', async () => {
        list([tenant({ id: 'ok' })]);
        const wrapper = await mountPage();
        api.mockRejectedValueOnce(new ApiError('Invalid', 422, { email: ['This employee account is not active.'] }));

        await wrapper.get('button[aria-label^="Enviar enlace de recuperación"]').trigger('click');
        await flushPromises();

        expect(toast.error).toHaveBeenCalledWith('No se pudo enviar el enlace', { description: 'This employee account is not active.' });
    });

    async function openPasswordDialog(wrapper: Awaited<ReturnType<typeof mountPage>>) {
        await wrapper.get('button[aria-label^="Cambiar la contraseña"]').trigger('click');
        await flushPromises();

        return document.body.querySelector('[role="dialog"]') as HTMLElement;
    }

    async function typePasswords(dialog: HTMLElement, password: string, confirmation: string) {
        for (const [selector, value] of [['#admin_password', password], ['#admin_password_confirmation', confirmation]] as const) {
            const input = dialog.querySelector(selector) as HTMLInputElement;
            input.value = value;
            input.dispatchEvent(new Event('input'));
        }
        await flushPromises(); // el Input emite su valor en el siguiente tick
    }

    it('changes the administrator password from a dialog and closes it', async () => {
        list([tenant({ id: 't1' })]);
        const wrapper = await mountPage();
        const dialog = await openPasswordDialog(wrapper);
        expect(dialog.textContent).toContain('ana@cuuno.test');

        await typePasswords(dialog, 'una-frase-larga-y-segura', 'una-frase-larga-y-segura');
        api.mockResolvedValueOnce(undefined).mockResolvedValueOnce({ data: [], meta: { base_domain: 'docket.test' } });
        dialog.querySelector('form')!.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(api).toHaveBeenNthCalledWith(
            2,
            '/platform/tenants/t1/admin/password',
            expect.objectContaining({ method: 'PUT', body: JSON.stringify({ password: 'una-frase-larga-y-segura', password_confirmation: 'una-frase-larga-y-segura' }) }),
        );
        expect(toast.success).toHaveBeenCalledWith('Contraseña cambiada', expect.anything());
        expect(document.body.querySelector('[role="dialog"]')).toBeNull();
    });

    it('keeps the dialog open with the policy error and clears the typed passwords', async () => {
        list([tenant({ id: 't1' })]);
        const wrapper = await mountPage();
        const dialog = await openPasswordDialog(wrapper);

        await typePasswords(dialog, 'password1234', 'password1234');
        api.mockRejectedValueOnce(new ApiError('Invalid', 422, { password: ['The password has appeared in a data leak.'] }));
        dialog.querySelector('form')!.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(document.body.querySelector('[role="dialog"]')!.textContent).toContain('data leak');
        expect((dialog.querySelector('#admin_password') as HTMLInputElement).value).toBe('');
    });

    it('does not offer a password change on a curaduría that is not ready', async () => {
        list([tenant({ id: 'susp', status: 'suspended' })]);

        const wrapper = await mountPage();

        expect(wrapper.get('button[aria-label^="Cambiar la contraseña"]').attributes('disabled')).toBeDefined();
    });
});
