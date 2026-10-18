<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ApiError } from '@/api/client';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';

const auth = usePlatformAuthStore();
const router = useRouter();
const route = useRoute();

const email = ref('');
const password = ref('');
const submitting = ref(false);
const error = ref('');

async function submit(): Promise<void> {
    submitting.value = true;
    error.value = '';

    try {
        await auth.login(email.value, password.value);
        const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null;
        // Solo rutas internas: un `redirect=https://...` no debe sacar al usuario del sitio.
        await router.push(redirect && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : { name: 'platform.tenants' });
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        error.value =
            e.status === 429
                ? 'Demasiados intentos fallidos. Espera unos minutos e inténtalo de nuevo.'
                : (e.errors?.email?.[0] ?? 'No se pudo iniciar sesión.');
        password.value = '';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="mx-auto flex min-h-screen max-w-sm flex-col justify-center p-6">
        <h1 class="text-2xl font-semibold">CuraduriAPP</h1>
        <p class="mt-1 text-sm text-neutral-500">Panel de plataforma</p>

        <form class="mt-8 space-y-4" @submit.prevent="submit">
            <div>
                <label for="email" class="block text-sm font-medium">Correo</label>
                <input
                    id="email"
                    v-model="email"
                    type="email"
                    autocomplete="username"
                    required
                    class="mt-1 w-full rounded border border-neutral-300 px-3 py-2"
                />
            </div>
            <div>
                <label for="password" class="block text-sm font-medium">Contraseña</label>
                <input
                    id="password"
                    v-model="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="mt-1 w-full rounded border border-neutral-300 px-3 py-2"
                />
            </div>

            <p v-if="error" role="alert" class="text-sm text-red-700">{{ error }}</p>

            <button
                type="submit"
                :disabled="submitting"
                class="w-full rounded bg-neutral-900 px-3 py-2 font-medium text-white disabled:opacity-50"
            >
                {{ submitting ? 'Entrando…' : 'Entrar' }}
            </button>
        </form>
    </div>
</template>
