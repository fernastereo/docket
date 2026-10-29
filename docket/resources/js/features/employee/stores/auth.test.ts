import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { ApiError } from '@/api/client';
import { useEmployeeAuthStore } from '@/features/employee/stores/auth';
import type { EmployeeUser } from '@/features/employee/types';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

const employee: EmployeeUser = {
    id: 'e1',
    name: 'Ana Pérez',
    email: 'ana@curaduria.test',
    roles: ['admin'],
    role_labels: ['Administrador'],
    mfa_enabled: false,
    mfa_required: true,
    mfa_verified: false,
};

describe('employee auth store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        api.mockReset();
    });

    it('talks to the employee API, not the platform one', async () => {
        api.mockResolvedValue({ data: employee });
        const auth = useEmployeeAuthStore();

        await auth.login('ana@curaduria.test', 'secreto');

        expect(api).toHaveBeenCalledWith('/employee/login', {
            method: 'POST',
            body: JSON.stringify({ email: 'ana@curaduria.test', password: 'secreto' }),
        });
        expect(auth.user).toEqual(employee);
    });

    it('fetchUser leaves no user when there is no session', async () => {
        api.mockRejectedValue(new ApiError('Unauthenticated', 401));
        const auth = useEmployeeAuthStore();

        await auth.fetchUser();

        expect(auth.user).toBeNull();
        expect(auth.loaded).toBe(true);
    });

    it('confirming MFA returns the recovery codes and refreshes the user', async () => {
        api.mockResolvedValue({ data: { recovery_codes: ['AAAAA-BBBBB'], user: { ...employee, mfa_enabled: true, mfa_verified: true } } });
        const auth = useEmployeeAuthStore();

        const codes = await auth.confirmMfa('123456');

        expect(api).toHaveBeenCalledWith('/employee/mfa/confirm', { method: 'POST', body: JSON.stringify({ code: '123456' }) });
        expect(codes).toEqual(['AAAAA-BBBBB']);
        expect(auth.user?.mfa_verified).toBe(true);
    });

    it('logout forgets the session even if the request fails', async () => {
        api.mockRejectedValue(new ApiError('Server error', 500));
        const auth = useEmployeeAuthStore();
        auth.user = employee;

        await expect(auth.logout()).rejects.toBeInstanceOf(ApiError);

        expect(auth.user).toBeNull();
    });
});
