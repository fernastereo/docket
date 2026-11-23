import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { ApiError } from '@/api/client';
import ResetPasswordPage from '@/features/platform/pages/ResetPasswordPage.vue';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

async function mountPage() {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/platform/reset-password/:token', name: 'platform.reset', component: ResetPasswordPage },
            { path: '/platform/login', name: 'platform.login', component: { template: '<p>login</p>' } },
            { path: '/platform/forgot-password', name: 'platform.forgot', component: { template: '<p>forgot</p>' } },
        ],
    });
    router.push('/platform/reset-password/tok-9');
    await router.isReady();
    const wrapper = mount(ResetPasswordPage, { global: { plugins: [router] } });
    await flushPromises();

    return wrapper;
}

describe('platform ResetPasswordPage', () => {
    // Con llaves a propósito (ver LoginPage.test.ts).
    beforeEach(() => {
        api.mockReset();
    });

    it('greets the user and saves the new password on the platform endpoint, then sends them to the login', async () => {
        api.mockResolvedValueOnce({ data: { name: 'Fernando Cueto', email: 'soporte@curaduria.app' } }).mockResolvedValueOnce(undefined);
        const wrapper = await mountPage();
        expect(api).toHaveBeenCalledWith('/platform/reset-password/tok-9');
        expect(wrapper.text()).toContain('Hola, Fernando Cueto');
        // La política de plataforma, no la de empleado.
        expect(wrapper.get('#password').attributes('minlength')).toBe('12');
        expect(wrapper.text()).toContain('Al menos 12 caracteres');

        await wrapper.get('#password').setValue('una-frase-larga-y-segura');
        await wrapper.get('#password_confirmation').setValue('una-frase-larga-y-segura');
        await wrapper.get('form').trigger('submit');
        await flushPromises();

        expect(api).toHaveBeenLastCalledWith(
            '/platform/reset-password/tok-9',
            expect.objectContaining({ method: 'POST', body: JSON.stringify({ password: 'una-frase-larga-y-segura', password_confirmation: 'una-frase-larga-y-segura' }) }),
        );
        expect(wrapper.text()).toContain('Contraseña guardada');
        expect(wrapper.get('a').attributes('href')).toBe('/platform/login');
    });

    it('says the link is not valid and offers a new one, mentioning how long they last', async () => {
        api.mockRejectedValueOnce(new ApiError('This link is not valid.', 404));

        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('Este enlace ya no es válido');
        expect(wrapper.text()).toContain('60 minutos');
        expect(wrapper.get('a[href="/platform/forgot-password"]').text()).toContain('Pedir un enlace nuevo');
    });
});
