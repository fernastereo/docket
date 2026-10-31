<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { CircleAlert, CircleCheck, Loader2 } from 'lucide-vue-next';
import { RouterLink, useRoute } from 'vue-router';
import { api, ApiError } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import AuthLayout from '@/features/auth/components/AuthLayout.vue';
import PasswordFields from '@/features/auth/components/PasswordFields.vue';
import type { InvitationInfo } from '@/features/employee/types';
import { readAppContext } from '@/lib/appContext';

/**
 * Pantalla del enlace de invitación (pública, en el subdominio de la curaduría).
 * Cuatro vistas: cargando → formulario para elegir la contraseña → listo; o
 * "enlace no válido". La API responde 404 igual para un enlace desconocido,
 * usado o vencido, y acá se muestra igual: no se revela cuál fue.
 */
type View = 'loading' | 'form' | 'done' | 'invalid' | 'failed';

const route = useRoute();
const { tenantName } = readAppContext();
const token = encodeURIComponent(String(route.params.token));

const view = ref<View>('loading');
const invitation = ref<InvitationInfo | null>(null);
const password = ref('');
const passwordConfirmation = ref('');
const submitting = ref(false);
const error = ref('');

onMounted(async () => {
    try {
        invitation.value = (await api<{ data: InvitationInfo }>(`/employee/invitation/${token}`)).data;
        view.value = 'form';
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        view.value = e.status === 404 ? 'invalid' : 'failed';
    }
});

async function submit(): Promise<void> {
    submitting.value = true;
    error.value = '';

    try {
        await api(`/employee/invitation/${token}/accept`, {
            method: 'POST',
            body: JSON.stringify({ password: password.value, password_confirmation: passwordConfirmation.value }),
        });
        view.value = 'done';
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        if (e.status === 404 || e.errors?.token) {
            // Venció o se usó mientras la persona escribía.
            view.value = 'invalid';
        } else if (e.status === 429) {
            error.value = 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.';
        } else {
            error.value = e.errors?.password?.[0] ?? 'No se pudo guardar la contraseña. Inténtalo de nuevo.';
            password.value = '';
            passwordConfirmation.value = '';
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <AuthLayout :aside-note="`${tenantName ?? 'Tu curaduría'} · acceso para el personal de la curaduría.`">
        <div v-if="view === 'loading'" class="space-y-4" aria-busy="true" aria-label="Comprobando la invitación">
            <Skeleton class="h-8 w-3/4" />
            <Skeleton class="h-4 w-full" />
            <Skeleton class="h-10 w-full" />
        </div>

        <template v-else-if="view === 'form' && invitation">
            <h1 class="text-2xl font-semibold tracking-tight">Hola, {{ invitation.name }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Elige una contraseña para entrar con <span class="font-medium text-foreground">{{ invitation.email }}</span
                >.
            </p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <PasswordFields id="password" v-model:password="password" v-model:confirmation="passwordConfirmation" />

                <p v-if="error" role="alert" class="flex items-start gap-2 text-sm text-destructive">
                    <CircleAlert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    {{ error }}
                </p>

                <Button type="submit" class="w-full" :disabled="submitting">
                    <Loader2 v-if="submitting" class="animate-spin" aria-hidden="true" />
                    {{ submitting ? 'Guardando…' : 'Guardar contraseña' }}
                </Button>
            </form>
        </template>

        <template v-else-if="view === 'done'">
            <CircleCheck class="size-8 text-primary" aria-hidden="true" />
            <h1 class="mt-4 text-2xl font-semibold tracking-tight">Contraseña guardada</h1>
            <p class="mt-1 text-sm text-muted-foreground">Ya puedes iniciar sesión con tu correo y tu nueva contraseña.</p>
            <Button as-child class="mt-8 w-full">
                <RouterLink :to="{ name: 'employee.login' }">Ir a iniciar sesión</RouterLink>
            </Button>
        </template>

        <template v-else-if="view === 'invalid'">
            <h1 class="text-2xl font-semibold tracking-tight">Este enlace ya no es válido</h1>
            <p role="alert" class="mt-1 text-sm text-muted-foreground">
                Pudo vencer (duran 7 días) o ya se usó. Pídele al administrador de tu curaduría que te envíe una invitación nueva.
            </p>
            <Button as-child variant="outline" class="mt-8 w-full">
                <RouterLink :to="{ name: 'employee.login' }">Ir a iniciar sesión</RouterLink>
            </Button>
        </template>

        <template v-else>
            <h1 class="text-2xl font-semibold tracking-tight">No pudimos comprobar el enlace</h1>
            <p role="alert" class="mt-1 text-sm text-muted-foreground">Recarga la página para intentarlo de nuevo.</p>
        </template>
    </AuthLayout>
</template>
