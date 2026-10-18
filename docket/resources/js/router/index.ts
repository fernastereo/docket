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
    if (!to.meta.requiresPlatformAuth && !to.meta.guestOnly) return true;

    const auth = usePlatformAuthStore();
    if (!auth.loaded) await auth.fetchUser();

    if (to.meta.requiresPlatformAuth && !auth.user) {
        return { name: 'platform.login', query: { redirect: to.fullPath } };
    }
    if (to.meta.guestOnly && auth.user) {
        return { name: 'platform.tenants' };
    }

    return true;
});
