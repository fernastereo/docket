import { createRouter, createWebHistory } from 'vue-router';

/**
 * Ruteo del lado del cliente. Laravel sirve el mismo shell (resources/views/
 * app.blade.php) para cualquier ruta no-API — ver routes/web.php y
 * routes/tenant.php — y vue-router resuelve la pantalla real acá.
 */
export const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'dashboard',
            component: () => import('@/features/dashboard/DashboardPage.vue'),
        },
    ],
});
