/**
 * Cliente de la API. Mismo origen que la app (Sanctum SPA con cookie
 * httpOnly + CSRF) — ver amendment de ADR-003. Nada de tokens en
 * localStorage.
 */

async function ensureCsrfCookie(): Promise<void> {
    await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
}

function readCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
    return match ? decodeURIComponent(match[1]) : null;
}

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly errors?: Record<string, string[]>,
    ) {
        super(message);
    }
}

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
    const method = (init.method ?? 'GET').toUpperCase();

    if (method !== 'GET') {
        await ensureCsrfCookie();
    }

    const response = await fetch(`/api${path}`, {
        ...init,
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': readCookie('XSRF-TOKEN') ?? '',
            ...init.headers,
        },
    });

    if (!response.ok) {
        const body = await response.json().catch(() => ({}));
        throw new ApiError(body.message ?? response.statusText, response.status, body.errors);
    }

    if (response.status === 204) {
        return undefined as T;
    }

    return response.json() as Promise<T>;
}
