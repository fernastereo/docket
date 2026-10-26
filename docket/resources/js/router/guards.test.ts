import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { router } from '@/router';
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
    mfa_enabled: true,
    mfa_verified: true,
    ...overrides,
});

async function signedInAs(current: PlatformUser | null) {
    const auth = usePlatformAuthStore();
    auth.user = current;
    auth.loaded = true;
}

describe('router guards', () => {
    beforeEach(async () => {
        setActivePinia(createPinia());
        api.mockReset();
        await router.replace('/');
    });

    it('sends a visitor without session to the login, remembering where they were going', async () => {
        await signedInAs(null);

        await router.push('/platform/tenants');

        expect(router.currentRoute.value.name).toBe('platform.login');
        expect(router.currentRoute.value.query.redirect).toBe('/platform/tenants');
    });

    it('keeps a password-only session out of the panel and sends it to the MFA screen', async () => {
        await signedInAs(user({ mfa_verified: false }));

        await router.push('/platform/tenants');

        expect(router.currentRoute.value.name).toBe('platform.mfa');
        expect(router.currentRoute.value.query.redirect).toBe('/platform/tenants');
    });

    it('lets a fully verified session into the panel', async () => {
        await signedInAs(user());

        await router.push('/platform/tenants');

        expect(router.currentRoute.value.name).toBe('platform.tenants');
    });

    it('the MFA screen needs a session but not a verified one', async () => {
        await signedInAs(user({ mfa_verified: false, mfa_enabled: false }));

        await router.push('/platform/mfa');

        expect(router.currentRoute.value.name).toBe('platform.mfa');
    });

    it('a logged-in user who still has to verify goes from the login to the MFA screen', async () => {
        await signedInAs(user({ mfa_verified: false }));

        await router.push('/platform/login');

        expect(router.currentRoute.value.name).toBe('platform.mfa');
    });
});
