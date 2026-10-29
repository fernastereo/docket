import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { needsMfa, type AuthSession } from '@/features/auth/types';
import { useEmployeeAuthStore } from '@/features/employee/stores/auth';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';
import { readAppContext, type AppContext } from '@/lib/appContext';

declare module 'vue-router' {
    interface RouteMeta {
        /** A qué dominio de identidad pertenece la ruta (sin esto es pública). */
        realm?: 'platform' | 'employee';
        /**
         * - full: sesión con el segundo factor al día.
         * - session: basta la contraseña (es la pantalla de MFA).
         * - guest: solo para quien no ha entrado (el login).
         */
        access?: 'full' | 'session' | 'guest';
    }
}

/**
 * Ruteo del lado del cliente. Laravel sirve el mismo shell (resources/views/
 * app.blade.php) para cualquier ruta no-API — ver routes/web.php y
 * routes/tenant.php — y vue-router resuelve la pantalla real acá.
 *
 * El contexto lo fija el servidor según el dominio: en el dominio central
 * solo existe el panel del proveedor (`/platform/*`, su API no existe en
 * subdominios de tenant); en un subdominio de curaduría, solo las pantallas
 * del personal de esa curaduría (su API es `/api/employee/*`).
 */
const platformRoutes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'dashboard',
        component: () => import('@/features/dashboard/DashboardPage.vue'),
    },
    {
        path: '/platform/login',
        name: 'platform.login',
        component: () => import('@/features/platform/pages/LoginPage.vue'),
        meta: { realm: 'platform', access: 'guest' },
    },
    {
        // Segundo factor: activarlo (primera vez) o verificarlo (cada login).
        path: '/platform/mfa',
        name: 'platform.mfa',
        component: () => import('@/features/platform/pages/MfaPage.vue'),
        meta: { realm: 'platform', access: 'session' },
    },
    {
        path: '/platform',
        component: () => import('@/features/platform/layouts/PlatformLayout.vue'),
        meta: { realm: 'platform', access: 'full' },
        children: [
            { path: '', redirect: { name: 'platform.tenants' } },
            {
                path: 'tenants',
                name: 'platform.tenants',
                component: () => import('@/features/platform/pages/TenantsPage.vue'),
            },
        ],
    },
];

const tenantRoutes: RouteRecordRaw[] = [
    {
        path: '/login',
        name: 'employee.login',
        component: () => import('@/features/employee/pages/LoginPage.vue'),
        meta: { realm: 'employee', access: 'guest' },
    },
    {
        path: '/mfa',
        name: 'employee.mfa',
        component: () => import('@/features/employee/pages/MfaPage.vue'),
        meta: { realm: 'employee', access: 'session' },
    },
    {
        path: '/',
        component: () => import('@/features/employee/layouts/StaffLayout.vue'),
        meta: { realm: 'employee', access: 'full' },
        children: [{ path: '', name: 'employee.home', component: () => import('@/features/employee/pages/HomePage.vue') }],
    },
    // Cualquier otra ruta de la curaduría vuelve al inicio (el guard decide si pide login).
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const realms = {
    platform: { useAuth: usePlatformAuthStore, login: 'platform.login', mfa: 'platform.mfa', home: 'platform.tenants' },
    employee: { useAuth: useEmployeeAuthStore, login: 'employee.login', mfa: 'employee.mfa', home: 'employee.home' },
} as const;

export function createAppRouter(context: AppContext) {
    const router = createRouter({
        history: createWebHistory(),
        routes: context === 'tenant' ? tenantRoutes : platformRoutes,
    });

    router.beforeEach(async (to) => {
        const realm = to.meta.realm;
        if (!realm || !to.meta.access) return true;

        const { useAuth, login, mfa, home } = realms[realm];
        const auth = useAuth();
        if (!auth.loaded) await auth.fetchUser();

        const user = (auth as unknown as AuthSession).user;

        if (to.meta.access !== 'guest' && !user) {
            return { name: login, query: { redirect: to.fullPath } };
        }
        // Con contraseña pero sin segundo factor al día, solo se llega a la pantalla de MFA.
        if (to.meta.access === 'full' && user && needsMfa(user)) {
            return { name: mfa, query: { redirect: to.fullPath } };
        }
        if (to.meta.access === 'guest' && user) {
            return { name: needsMfa(user) ? mfa : home };
        }

        return true;
    });

    return router;
}

export const router = createAppRouter(readAppContext().context);
