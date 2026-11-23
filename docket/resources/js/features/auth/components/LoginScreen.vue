<script setup lang="ts">
import { ref } from 'vue';
import { CircleAlert, Eye, EyeOff, Loader2 } from 'lucide-vue-next';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { ApiError } from '@/api/client';
import { Button } from '@/components/ui/button';
import { IconButton } from '@/components/ui/icon-button';
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
    /** Lo que destaca el panel de marca (solo pantallas grandes): la curaduría, o "Panel de plataforma". */
    asideTitle: string;
    asideNote?: string;
    /** Línea de ayuda bajo el formulario (qué hacer si no se puede entrar). */
    help?: string;
    /** Ruta de "¿Olvidaste tu contraseña?"; sin ella, el enlace no aparece (plataforma no lo tiene). */
    forgotRoute?: string;
}>();

const router = useRouter();
const route = useRoute();

const email = ref('');
const password = ref('');
const visible = ref(false);
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
    <AuthLayout :aside-title="asideTitle" :aside-note="asideNote">
        <h1 class="text-2xl font-semibold tracking-tight">Iniciar sesión</h1>
        <p class="mt-1 text-sm text-muted-foreground">{{ subtitle }}</p>

        <form class="mt-8 space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="email">Correo</Label>
                <Input id="email" v-model="email" type="email" autocomplete="username" required />
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between gap-4">
                    <Label for="password">Contraseña</Label>
                    <RouterLink v-if="forgotRoute" :to="{ name: forgotRoute }" class="text-sm text-muted-foreground transition-colors hover:text-action">
                        ¿Olvidaste tu contraseña?
                    </RouterLink>
                </div>
                <div class="relative">
                    <Input id="password" v-model="password" :type="visible ? 'text' : 'password'" class="pr-11" autocomplete="current-password" required />
                    <IconButton
                        :label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        class="absolute top-0.5 right-0.5"
                        @click="visible = !visible"
                    >
                        <EyeOff v-if="visible" aria-hidden="true" />
                        <Eye v-else aria-hidden="true" />
                    </IconButton>
                </div>
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

        <p v-if="help" class="mt-8 text-sm text-muted-foreground">{{ help }}</p>
    </AuthLayout>
</template>
