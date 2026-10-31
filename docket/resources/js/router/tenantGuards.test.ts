import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useEmployeeAuthStore } from '@/features/employee/stores/auth';
import type { EmployeeUser } from '@/features/employee/types';
import { createAppRouter } from '@/router';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

const employee = (overrides: Partial<EmployeeUser> = {}): EmployeeUser => ({
    id: 'e1',
    name: 'Ana Pérez',
    email: 'ana@curaduria.test',
    roles: ['architect'],
    role_labels: ['Arquitecto'],
    mfa_enabled: false,
    mfa_required: false,
    mfa_verified: true,
    ...overrides,
});

function routerFor(current: EmployeeUser | null) {
    const auth = useEmployeeAuthStore();
    auth.user = current;
    auth.loaded = true;

    return createAppRouter('tenant');
}

describe('router guards inside a curaduría', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        api.mockReset();
    });

    it('sends a visitor without session to the login, remembering the destination', async () => {
        const router = routerFor(null);

        await router.push('/');

        expect(router.currentRoute.value.name).toBe('employee.login');
        expect(router.currentRoute.value.query.redirect).toBe('/');
    });

    it('lets a verified session into the home', async () => {
        const router = routerFor(employee());

        await router.push('/');

        expect(router.currentRoute.value.name).toBe('employee.home');
    });

    it('keeps a password-only session of an admin on the MFA screen', async () => {
        const router = routerFor(employee({ roles: ['admin'], mfa_required: true, mfa_verified: false }));

        await router.push('/');

        expect(router.currentRoute.value.name).toBe('employee.mfa');
    });

    it('also stops a verified session whose role now requires MFA it has not set up', async () => {
        const router = routerFor(employee({ mfa_required: true, mfa_enabled: false, mfa_verified: true }));

        await router.push('/');

        expect(router.currentRoute.value.name).toBe('employee.mfa');
    });

    it('does not ask MFA from a role where it is optional and not enabled', async () => {
        const router = routerFor(employee({ mfa_required: false, mfa_enabled: false, mfa_verified: true }));

        await router.push('/');

        expect(router.currentRoute.value.name).toBe('employee.home');
    });

    it('a signed-in employee skips the login screen', async () => {
        const router = routerFor(employee());

        await router.push('/login');

        expect(router.currentRoute.value.name).toBe('employee.home');
    });

    it('unknown paths go back to the home, and then to the login if there is no session', async () => {
        const router = routerFor(null);

        await router.push('/platform/tenants');

        expect(router.currentRoute.value.name).toBe('employee.login');
    });

    it('the platform panel does not exist inside a curaduría', () => {
        const router = createAppRouter('tenant');

        expect(router.hasRoute('platform.login')).toBe(false);
        expect(router.hasRoute('platform.tenants')).toBe(false);
    });
});
