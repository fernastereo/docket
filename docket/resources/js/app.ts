import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from '@/App.vue';
import { UNAUTHORIZED_EVENT } from '@/api/client';
import { initTheme } from '@/composables/useTheme';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';
import { router } from '@/router';

initTheme();

const app = createApp(App).use(createPinia()).use(router);

// Una respuesta 401 en plena sesión (venció por inactividad, o la cerraron)
// manda al login conservando a dónde iba.
window.addEventListener(UNAUTHORIZED_EVENT, () => {
    const auth = usePlatformAuthStore();
    const wasLoggedIn = auth.user !== null;
    auth.clear();

    if (wasLoggedIn) {
        router.push({ name: 'platform.login', query: { redirect: router.currentRoute.value.fullPath } });
    }
});

app.mount('#app');
