import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createMemoryHistory, createRouter } from 'vue-router';
import { ApiError } from '@/api/client';
import MfaPage from '@/features/platform/pages/MfaPage.vue';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';
import type { PlatformUser } from '@/features/platform/types';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

const user = (overrides: Partial<PlatformUser> = {}): PlatformUser => ({
    id: 'u1',
    name: 'Soporte',
    email: 'soporte@curaduria.app',
    mfa_enabled: false,
    mfa_verified: false,
    ...overrides,
});

async function mountPage(current: PlatformUser, initial = '/platform/mfa') {
    const pinia = createPinia();
    setActivePinia(pinia);
    usePlatformAuthStore().user = current;
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/platform/mfa', name: 'platform.mfa', component: MfaPage },
            { path: '/platform/tenants', name: 'platform.tenants', component: { template: '<p>tenants</p>' } },
            { path: '/platform/login', name: 'platform.login', component: { template: '<p>login</p>' } },
        ],
    });
    router.push(initial);
    await router.isReady();

    const wrapper = mount(MfaPage, { global: { plugins: [pinia, router] } });
    await flushPromises();

    return { wrapper, router };
}

async function submitCode(wrapper: Awaited<ReturnType<typeof mountPage>>['wrapper'], value: string) {
    await wrapper.get('#code').setValue(value);
    await wrapper.get('form').trigger('submit');
    await flushPromises();
}

describe('MfaPage', () => {
    // Con llaves a propósito (ver LoginPage.test.ts).
    beforeEach(() => {
        api.mockReset();
    });

    it('asks for the QR when the account has no MFA yet and shows the manual key', async () => {
        api.mockResolvedValue({ data: { secret: 'AAAAAAAAAAAA', uri: 'otpauth://totp/x', qr: 'data:image/svg+xml;base64,AAAA' } });

        const { wrapper } = await mountPage(user());

        expect(api).toHaveBeenCalledWith('/platform/mfa/setup', { method: 'POST' });
        expect(wrapper.get('img').attributes('src')).toBe('data:image/svg+xml;base64,AAAA');
        expect(wrapper.text()).toContain('AAAAAAAAAAAA');
    });

    it('shows the recovery codes once after activating, then goes to the panel', async () => {
        api.mockResolvedValueOnce({ data: { secret: 'AAAAAAAAAAAA', uri: 'u', qr: 'data:image/svg+xml;base64,AAAA' } });
        const { wrapper, router } = await mountPage(user());
        api.mockResolvedValueOnce({
            data: { recovery_codes: ['AAAAA-BBBBB', 'CCCCC-DDDDD'], user: user({ mfa_enabled: true, mfa_verified: true }) },
        });

        await submitCode(wrapper, '123456');

        expect(api).toHaveBeenLastCalledWith('/platform/mfa/confirm', { method: 'POST', body: JSON.stringify({ code: '123456' }) });
        expect(wrapper.text()).toContain('AAAAA-BBBBB');
        expect(wrapper.text()).toContain('No se volverán a mostrar');

        await wrapper.get('button').trigger('click');
        await flushPromises();

        expect(router.currentRoute.value.name).toBe('platform.tenants');
    });

    it('verifies the code on an account that already has MFA and goes where the user was heading', async () => {
        const { wrapper, router } = await mountPage(user({ mfa_enabled: true }), '/platform/mfa?redirect=/platform/tenants');
        api.mockResolvedValue({ data: user({ mfa_enabled: true, mfa_verified: true }) });

        await submitCode(wrapper, '654321');

        expect(api).toHaveBeenCalledWith('/platform/mfa/verify', { method: 'POST', body: JSON.stringify({ code: '654321' }) });
        expect(router.currentRoute.value.name).toBe('platform.tenants');
    });

    it('shows the error for a wrong code and clears the field', async () => {
        const { wrapper, router } = await mountPage(user({ mfa_enabled: true }));
        api.mockRejectedValue(new ApiError('Invalid', 422, { code: ['The code is not valid.'] }));

        await submitCode(wrapper, '000000');

        expect(wrapper.get('[role="alert"]').text()).toContain('not valid');
        expect((wrapper.get('#code').element as HTMLInputElement).value).toBe('');
        expect(router.currentRoute.value.name).toBe('platform.mfa');
    });

    it('explains a lockout in plain words', async () => {
        const { wrapper } = await mountPage(user({ mfa_enabled: true }));
        api.mockRejectedValue(new ApiError('Too many attempts', 429));

        await submitCode(wrapper, '000000');

        expect(wrapper.get('[role="alert"]').text()).toContain('Demasiados intentos');
    });
});
