import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { ApiError } from '@/api/client';
import InvitationPage from '@/features/employee/pages/InvitationPage.vue';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

const person = { data: { name: 'Ana Pérez', email: 'ana@curaduria.test' } };

async function mountPage() {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/invitation/:token', name: 'employee.invitation', component: InvitationPage },
            { path: '/login', name: 'employee.login', component: { template: '<p>login</p>' } },
        ],
    });
    router.push('/invitation/tok-123');
    await router.isReady();
    const wrapper = mount(InvitationPage, { global: { plugins: [router] } });
    await flushPromises();

    return wrapper;
}

async function fillAndSubmit(wrapper: Awaited<ReturnType<typeof mountPage>>, password = 'una-frase-larga-y-segura', confirmation = password) {
    await wrapper.get('#password').setValue(password);
    await wrapper.get('#password_confirmation').setValue(confirmation);
    await wrapper.get('form').trigger('submit');
    await flushPromises();
}

describe('employee InvitationPage', () => {
    // Con llaves a propósito (ver platform/pages/LoginPage.test.ts).
    beforeEach(() => {
        api.mockReset();
    });

    it('greets the invited person by name and email', async () => {
        api.mockResolvedValueOnce(person);

        const wrapper = await mountPage();

        expect(api).toHaveBeenCalledWith('/employee/invitation/tok-123');
        expect(wrapper.text()).toContain('Hola, Ana Pérez');
        expect(wrapper.text()).toContain('ana@curaduria.test');
    });

    it('sets the password and offers the way to sign in', async () => {
        api.mockResolvedValueOnce(person).mockResolvedValueOnce(undefined);
        const wrapper = await mountPage();

        await fillAndSubmit(wrapper);

        expect(api).toHaveBeenLastCalledWith(
            '/employee/invitation/tok-123/accept',
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify({ password: 'una-frase-larga-y-segura', password_confirmation: 'una-frase-larga-y-segura' }),
            }),
        );
        expect(wrapper.text()).toContain('Contraseña guardada');
        expect(wrapper.get('a').attributes('href')).toBe('/login');
    });

    it('shows the password error, clears the fields and keeps the link usable', async () => {
        api.mockResolvedValueOnce(person).mockRejectedValueOnce(new ApiError('Invalid', 422, { password: ['The password has appeared in a data leak.'] }));
        const wrapper = await mountPage();

        await fillAndSubmit(wrapper, 'password1234');

        expect(wrapper.get('[role="alert"]').text()).toContain('data leak');
        expect((wrapper.get('#password').element as HTMLInputElement).value).toBe('');
        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('treats an unknown, used or expired link the same way', async () => {
        api.mockRejectedValueOnce(new ApiError('This invitation is not valid.', 404));

        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('Este enlace ya no es válido');
        expect(wrapper.find('form').exists()).toBe(false);
    });

    it('falls back to the invalid view when the link expires while typing', async () => {
        api.mockResolvedValueOnce(person).mockRejectedValueOnce(new ApiError('Gone', 404));
        const wrapper = await mountPage();

        await fillAndSubmit(wrapper);

        expect(wrapper.text()).toContain('Este enlace ya no es válido');
    });

    it('asks to retry when the server fails instead of calling the link invalid', async () => {
        api.mockRejectedValueOnce(new ApiError('Server error', 500));

        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('No pudimos comprobar el enlace');
        expect(wrapper.text()).not.toContain('ya no es válido');
    });

    it('says to wait when throttled', async () => {
        api.mockResolvedValueOnce(person).mockRejectedValueOnce(new ApiError('Too many', 429));
        const wrapper = await mountPage();

        await fillAndSubmit(wrapper);

        expect(wrapper.get('[role="alert"]').text()).toContain('Demasiados intentos');
    });
});
