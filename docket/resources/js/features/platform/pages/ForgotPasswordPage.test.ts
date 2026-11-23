import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import ForgotPasswordPage from '@/features/platform/pages/ForgotPasswordPage.vue';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

async function mountPage() {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/platform/forgot-password', name: 'platform.forgot', component: ForgotPasswordPage },
            { path: '/platform/login', name: 'platform.login', component: { template: '<p>login</p>' } },
        ],
    });
    router.push('/platform/forgot-password');
    await router.isReady();

    return mount(ForgotPasswordPage, { global: { plugins: [router] } });
}

describe('platform ForgotPasswordPage', () => {
    // Con llaves a propósito (ver LoginPage.test.ts).
    beforeEach(() => {
        api.mockReset();
    });

    it('asks the platform endpoint and confirms the same way, naming the panel', async () => {
        api.mockResolvedValueOnce({ message: 'ok' });
        const wrapper = await mountPage();

        await wrapper.get('#email').setValue('soporte@curaduria.app');
        await wrapper.get('form').trigger('submit');
        await flushPromises();

        expect(api).toHaveBeenCalledWith('/platform/forgot-password', expect.objectContaining({ method: 'POST', body: JSON.stringify({ email: 'soporte@curaduria.app' }) }));
        expect(wrapper.get('[role="status"]').text()).toContain('Si soporte@curaduria.app tiene una cuenta en el panel de plataforma');
        expect(wrapper.get('a').attributes('href')).toBe('/platform/login');
    });
});
