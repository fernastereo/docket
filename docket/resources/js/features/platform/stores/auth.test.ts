import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { ApiError } from '@/api/client';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';

const api = vi.hoisted(() => vi.fn());
vi.mock('@/api/client', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/api/client')>()),
    api,
}));

const user = { id: 'u1', name: 'Soporte Uno', email: 'soporte@curaduria.app' };

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
});
