import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { ApiError } from '@/api/client';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

const user = { id: 'u1', name: 'Soporte Uno', email: 'soporte@curaduria.app', mfa_enabled: true, mfa_verified: false };

describe('platform auth store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        api.mockReset();
    });

    it('login stores the user and marks the session as loaded', async () => {
        api.mockResolvedValue({ data: user });
        const auth = usePlatformAuthStore();

        await auth.login('soporte@curaduria.app', 'secret');

        expect(api).toHaveBeenCalledWith('/platform/login', {
            method: 'POST',
            body: JSON.stringify({ email: 'soporte@curaduria.app', password: 'secret' }),
        });
        expect(auth.user).toEqual(user);
        expect(auth.loaded).toBe(true);
    });

    it('a failed login leaves no user and lets the error through', async () => {
        api.mockRejectedValue(new ApiError('Invalid', 422, { email: ['Bad'] }));
        const auth = usePlatformAuthStore();

        await expect(auth.login('a@b.co', 'x')).rejects.toBeInstanceOf(ApiError);

        expect(auth.user).toBeNull();
    });

    it('fetchUser keeps the user from the session', async () => {
        api.mockResolvedValue({ data: user });
        const auth = usePlatformAuthStore();

        await auth.fetchUser();

        expect(auth.user).toEqual(user);
        expect(auth.loaded).toBe(true);
    });

    it('fetchUser without a session leaves no user, without throwing', async () => {
        api.mockRejectedValue(new ApiError('Unauthenticated.', 401));
        const auth = usePlatformAuthStore();

        await auth.fetchUser();

        expect(auth.user).toBeNull();
        expect(auth.loaded).toBe(true);
    });

    it('logout forgets the user even if the request fails', async () => {
        api.mockResolvedValueOnce({ data: user });
        const auth = usePlatformAuthStore();
        await auth.login('a@b.co', 'x');

        api.mockRejectedValueOnce(new ApiError('Session expired.', 401));
        await auth.logout().catch(() => undefined);

        expect(auth.user).toBeNull();
    });

    it('startMfaSetup returns the QR data without touching the user', async () => {
        api.mockResolvedValue({ data: { secret: 'ABC', uri: 'otpauth://x', qr: 'data:image/svg+xml;base64,AA' } });
        const auth = usePlatformAuthStore();

        const setup = await auth.startMfaSetup();

        expect(api).toHaveBeenCalledWith('/platform/mfa/setup', { method: 'POST' });
        expect(setup.secret).toBe('ABC');
        expect(auth.user).toBeNull();
    });

    it('confirmMfa returns the recovery codes and updates the user', async () => {
        const verified = { ...user, mfa_enabled: true, mfa_verified: true };
        api.mockResolvedValue({ data: { recovery_codes: ['AAAAA-BBBBB'], user: verified } });
        const auth = usePlatformAuthStore();

        const codes = await auth.confirmMfa('123456');

        expect(codes).toEqual(['AAAAA-BBBBB']);
        expect(auth.user).toEqual(verified);
    });

    it('verifyMfa marks the session as verified', async () => {
        api.mockResolvedValue({ data: { ...user, mfa_enabled: true, mfa_verified: true } });
        const auth = usePlatformAuthStore();

        await auth.verifyMfa('123456');

        expect(auth.user?.mfa_verified).toBe(true);
    });

    it('a rejected code lets the error through and keeps the user as it was', async () => {
        api.mockResolvedValueOnce({ data: user });
        const auth = usePlatformAuthStore();
        await auth.login('a@b.co', 'x');
        api.mockRejectedValueOnce(new ApiError('Invalid', 422, { code: ['Bad'] }));

        await expect(auth.verifyMfa('000000')).rejects.toBeInstanceOf(ApiError);

        expect(auth.user?.mfa_verified).toBe(false);
    });
});
