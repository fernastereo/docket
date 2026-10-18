import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { api, ApiError, UNAUTHORIZED_EVENT } from '@/api/client';

function jsonResponse(body: unknown, status = 200): Response {
    return new Response(status === 204 ? null : JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/json' },
    });
}

describe('api client', () => {
    const fetchMock = vi.fn();

    beforeEach(() => {
        fetchMock.mockReset();
        vi.stubGlobal('fetch', fetchMock);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('asks for the CSRF cookie before a write, not before a read', async () => {
        fetchMock.mockImplementation(() => Promise.resolve(jsonResponse({})));

        await api('/x');
        expect(fetchMock.mock.calls.map((c) => c[0])).toEqual(['/api/x']);

        fetchMock.mockClear();
        await api('/x', { method: 'POST', body: '{}' });
        expect(fetchMock.mock.calls.map((c) => c[0])).toEqual(['/sanctum/csrf-cookie', '/api/x']);
    });

    it('throws an ApiError with the status and the field errors', async () => {
        fetchMock.mockResolvedValue(jsonResponse({ message: 'Invalid', errors: { email: ['Bad'] } }, 422));

        const error = (await api('/x').catch((e: unknown) => e)) as ApiError;

        expect(error).toBeInstanceOf(ApiError);
        expect(error.status).toBe(422);
        expect(error.errors).toEqual({ email: ['Bad'] });
    });

    it('announces a 401 so the app can send the user to the login', async () => {
        fetchMock.mockResolvedValue(jsonResponse({ message: 'Unauthenticated.' }, 401));
        const listener = vi.fn();
        window.addEventListener(UNAUTHORIZED_EVENT, listener);

        await api('/x').catch(() => undefined);

        window.removeEventListener(UNAUTHORIZED_EVENT, listener);
        expect(listener).toHaveBeenCalledTimes(1);
    });

    it('returns undefined for a 204', async () => {
        fetchMock.mockResolvedValue(jsonResponse(null, 204));

        await expect(api('/x')).resolves.toBeUndefined();
    });
});
