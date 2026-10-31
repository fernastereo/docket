<script setup lang="ts">
import { ref } from 'vue';
import { CircleAlert, Loader2 } from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';
import { ApiError } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/features/auth/components/AuthLayout.vue';
import type { AuthSession } from '@/features/auth/types';

const props = defineProps<{
    auth: AuthSession;
    /** Nombre de la ruta a la que se va tras entrar (si no hay `redirect`). */
    homeRoute: string;
    /** Línea bajo el título: de qué cuenta se trata. */
    subtitle: string;
    /** Nota al pie del panel de marca (solo pantallas grandes). */
    asideNote: string;
}>();

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
        await props.auth.login(email.value, password.value);
        const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null;
        // Solo rutas internas: un `redirect=https://...` no debe sacar al usuario del sitio.
        await router.push(redirect && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : { name: props.homeRoute });
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
    <AuthLayout :aside-note="asideNote">
        <h1 class="text-2xl font-semibold tracking-tight">Iniciar sesión</h1>
        <p class="mt-1 text-sm text-muted-foreground">{{ subtitle }}</p>

        <form class="mt-8 space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="email">Correo</Label>
                <Input id="email" v-model="email" type="email" autocomplete="username" required />
            </div>
            <div class="space-y-2">
                <Label for="password">Contraseña</Label>
                <Input id="password" v-model="password" type="password" autocomplete="current-password" required />
            </div>

            <p v-if="error" role="alert" class="flex items-start gap-2 text-sm text-destructive">
                <CircleAlert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                {{ error }}
            </p>

            <Button type="submit" class="w-full" :disabled="submitting">
                <Loader2 v-if="submitting" class="animate-spin" aria-hidden="true" />
                {{ submitting ? 'Entrando…' : 'Entrar' }}
            </Button>
        </form>
    </AuthLayout>
</template>
