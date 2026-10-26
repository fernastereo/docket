import { createRouter, createWebHistory } from 'vue-router';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';

/**
 * Ruteo del lado del cliente. Laravel sirve el mismo shell (resources/views/
 * app.blade.php) para cualquier ruta no-API — ver routes/web.php y
 * routes/tenant.php — y vue-router resuelve la pantalla real acá.
 *
 * `/platform/*` es el panel del proveedor y solo existe en el dominio
 * central (su API, `/api/platform/*`, no existe en subdominios de tenant).
 */
export const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'dashboard',
            component: () => import('@/features/dashboard/DashboardPage.vue'),
        },
        {
            path: '/platform/login',
            name: 'platform.login',
            component: () => import('@/features/platform/pages/LoginPage.vue'),
            meta: { guestOnly: true },
        },
        {
            // Segundo factor: activarlo (primera vez) o verificarlo (cada login).
            path: '/platform/mfa',
            name: 'platform.mfa',
            component: () => import('@/features/platform/pages/MfaPage.vue'),
            meta: { requiresPlatformSession: true },
        },
        {
            path: '/platform',
            component: () => import('@/features/platform/layouts/PlatformLayout.vue'),
            meta: { requiresPlatformAuth: true },
            children: [
                { path: '', redirect: { name: 'platform.tenants' } },
                {
                    path: 'tenants',
                    name: 'platform.tenants',
                    component: () => import('@/features/platform/pages/TenantsPage.vue'),
                },
            ],
        },
    ],
});

router.beforeEach(async (to) => {
    if (!to.meta.requiresPlatformAuth && !to.meta.requiresPlatformSession && !to.meta.guestOnly) return true;

    const auth = usePlatformAuthStore();
    if (!auth.loaded) await auth.fetchUser();

    if ((to.meta.requiresPlatformAuth || to.meta.requiresPlatformSession) && !auth.user) {
        return { name: 'platform.login', query: { redirect: to.fullPath } };
    }
    // Con contraseña pero sin segundo factor verificado, solo se llega a la pantalla de MFA.
    if (to.meta.requiresPlatformAuth && auth.user && !auth.user.mfa_verified) {
        return { name: 'platform.mfa', query: { redirect: to.fullPath } };
    }
    if (to.meta.guestOnly && auth.user) {
        return auth.user.mfa_verified ? { name: 'platform.tenants' } : { name: 'platform.mfa' };
    }

    return true;
});
