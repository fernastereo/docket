import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createMemoryHistory, createRouter } from 'vue-router';
import { ApiError } from '@/api/client';
import LoginPage from '@/features/platform/pages/LoginPage.vue';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

async function mountLogin(initial = '/platform/login') {
    const pinia = createPinia();
    setActivePinia(pinia);
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/platform/login', name: 'platform.login', component: LoginPage },
            { path: '/platform/tenants', name: 'platform.tenants', component: { template: '<p>tenants</p>' } },
            { path: '/platform/other', name: 'other', component: { template: '<p>other</p>' } },
        ],
    });
    router.push(initial);
    await router.isReady();

    const wrapper = mount(LoginPage, { global: { plugins: [pinia, router] } });

    return { wrapper, router };
}

async function fill(wrapper: Awaited<ReturnType<typeof mountLogin>>['wrapper']) {
    await wrapper.get('#email').setValue('soporte@curaduria.app');
    await wrapper.get('#password').setValue('secreto');
    await wrapper.get('form').trigger('submit');
    await flushPromises();
}

describe('LoginPage', () => {
    // Con llaves a propósito: mockReset() devuelve el propio mock, y vitest trata
    // lo que devuelve un beforeEach como función de limpieza y lo ejecutaría.
    beforeEach(() => {
        api.mockReset();
    });

    it('goes to the tenants panel after a successful login', async () => {
        api.mockResolvedValue({ data: { id: 'u1', name: 'Soporte', email: 'soporte@curaduria.app', mfa_enabled: true, mfa_verified: false } });
        const { wrapper, router } = await mountLogin();

        await fill(wrapper);

        expect(router.currentRoute.value.name).toBe('platform.tenants');
    });

    it('goes back to where the user was heading, when it is an internal path', async () => {
        api.mockResolvedValue({ data: { id: 'u1', name: 'Soporte', email: 'soporte@curaduria.app', mfa_enabled: true, mfa_verified: false } });
        const { wrapper, router } = await mountLogin('/platform/login?redirect=/platform/other');

        await fill(wrapper);

        expect(router.currentRoute.value.fullPath).toBe('/platform/other');
    });

    it('ignores a redirect to another site', async () => {
        api.mockResolvedValue({ data: { id: 'u1', name: 'Soporte', email: 'soporte@curaduria.app', mfa_enabled: true, mfa_verified: false } });
        const { wrapper, router } = await mountLogin('/platform/login?redirect=//evil.example');

        await fill(wrapper);

        expect(router.currentRoute.value.name).toBe('platform.tenants');
    });

    it('shows the credentials error and clears the password', async () => {
        api.mockRejectedValue(new ApiError('Invalid', 422, { email: ['These credentials do not match our records.'] }));
        const { wrapper } = await mountLogin();

        await fill(wrapper);

        expect(wrapper.get('[role="alert"]').text()).toContain('do not match');
        expect((wrapper.get('#password').element as HTMLInputElement).value).toBe('');
    });

    it('explains a lockout in plain words', async () => {
        api.mockRejectedValue(new ApiError('Too many attempts', 429));
        const { wrapper } = await mountLogin();

        await fill(wrapper);

        expect(wrapper.get('[role="alert"]').text()).toContain('Demasiados intentos');
    });
});
