import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from '@/App.vue';
import { UNAUTHORIZED_EVENT } from '@/api/client';
import { initTheme } from '@/composables/useTheme';
import { useEmployeeAuthStore } from '@/features/employee/stores/auth';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';
import { readAppContext } from '@/lib/appContext';
import { router } from '@/router';

initTheme();

const app = createApp(App).use(createPinia()).use(router);
const { context } = readAppContext();

// Una respuesta 401 en plena sesión (venció por inactividad, o la cerraron)
// manda al login conservando a dónde iba.
window.addEventListener(UNAUTHORIZED_EVENT, () => {
    const auth = context === 'tenant' ? useEmployeeAuthStore() : usePlatformAuthStore();
    const wasLoggedIn = auth.user !== null;
    auth.clear();

    if (wasLoggedIn) {
        router.push({
            name: context === 'tenant' ? 'employee.login' : 'platform.login',
            query: { redirect: router.currentRoute.value.fullPath },
        });
    }
});

app.mount('#app');
