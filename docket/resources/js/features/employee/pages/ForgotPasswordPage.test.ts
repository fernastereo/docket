import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { ApiError } from '@/api/client';
import ForgotPasswordPage from '@/features/employee/pages/ForgotPasswordPage.vue';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

async function mountPage() {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/forgot-password', name: 'employee.forgot', component: ForgotPasswordPage },
            { path: '/login', name: 'employee.login', component: { template: '<p>login</p>' } },
        ],
    });
    router.push('/forgot-password');
    await router.isReady();

    return mount(ForgotPasswordPage, { global: { plugins: [router] } });
}

async function submit(wrapper: Awaited<ReturnType<typeof mountPage>>, email = 'ana@curaduria.test') {
    await wrapper.get('#email').setValue(email);
    await wrapper.get('form').trigger('submit');
    await flushPromises();
}

describe('employee ForgotPasswordPage', () => {
    // Con llaves a propósito (ver platform/pages/LoginPage.test.ts).
    beforeEach(() => {
        api.mockReset();
        document.getElementById('app')?.remove();
    });

    it('asks for the email and offers the way back to the login', async () => {
        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('Recuperar contraseña');
        expect(wrapper.get('a').attributes('href')).toBe('/login');
    });

    it('sends the email and confirms with the same message, without saying whether the account exists', async () => {
        api.mockResolvedValueOnce({ message: 'ok' });
        const wrapper = await mountPage();

        await submit(wrapper);

        expect(api).toHaveBeenCalledWith('/employee/forgot-password', expect.objectContaining({ method: 'POST', body: JSON.stringify({ email: 'ana@curaduria.test' }) }));
        expect(wrapper.get('[role="status"]').text()).toContain('Si ana@curaduria.test tiene una cuenta');
        expect(wrapper.find('form').exists()).toBe(false);
    });

    it('lets the person try another email', async () => {
        api.mockResolvedValueOnce({});
        const wrapper = await mountPage();
        await submit(wrapper);

        await wrapper.get('button[type="button"]').trigger('click');

        expect(wrapper.find('form').exists()).toBe(true);
        expect((wrapper.get('#email').element as HTMLInputElement).value).toBe('');
    });

    it('says to wait when throttled and keeps the form', async () => {
        api.mockRejectedValueOnce(new ApiError('Too many', 429));
        const wrapper = await mountPage();

        await submit(wrapper);

        expect(wrapper.get('[role="alert"]').text()).toContain('Demasiados intentos');
        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('shows the validation error for a malformed email', async () => {
        api.mockRejectedValueOnce(new ApiError('Invalid', 422, { email: ['El correo no es válido.'] }));
        const wrapper = await mountPage();

        await submit(wrapper, 'no-es-un-correo');

        expect(wrapper.get('[role="alert"]').text()).toContain('El correo no es válido.');
    });
});
