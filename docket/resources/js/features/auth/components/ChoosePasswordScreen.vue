<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { CircleAlert, CircleCheck, Loader2 } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';
import { api, ApiError } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import AuthLayout from '@/features/auth/components/AuthLayout.vue';
import PasswordFields from '@/features/auth/components/PasswordFields.vue';
import type { PasswordLinkOwner } from '@/features/auth/types';

/**
 * Pantalla de un enlace del correo para elegir contraseña (invitación de un
 * empleado, o recuperación de empleado o de plataforma). Cuatro vistas: cargando →
 * formulario → listo; o "enlace no válido". La API responde 404 igual para un
 * enlace desconocido, usado o vencido, y acá se muestra igual: no se revela cuál fue.
 */
const props = defineProps<{
    /** GET que dice a quién pertenece el enlace. */
    showUrl: string;
    /** POST que guarda la contraseña. */
    submitUrl: string;
    loginRoute: string;
    /** Con enlace vencido o usado: ruta para pedir uno nuevo ("¿Olvidaste tu contraseña?"). */
    forgotRoute?: string;
    asideTitle: string;
    /** Qué hacer cuando el enlace ya no vale (la duración y a quién acudir cambian según el dominio). */
    invalidHelp: string;
    /** Largo mínimo de la política que aplica (empleado o plataforma). */
    minLength: number;
}>();

type View = 'loading' | 'form' | 'done' | 'invalid' | 'failed';

const view = ref<View>('loading');
const owner = ref<PasswordLinkOwner | null>(null);
const password = ref('');
const passwordConfirmation = ref('');
const submitting = ref(false);
const error = ref('');

onMounted(async () => {
    try {
        owner.value = (await api<{ data: PasswordLinkOwner }>(props.showUrl)).data;
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
        await api(props.submitUrl, {
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
    <AuthLayout :aside-title="asideTitle">
        <div v-if="view === 'loading'" class="space-y-4" aria-busy="true" aria-label="Comprobando el enlace">
            <Skeleton class="h-8 w-3/4" />
            <Skeleton class="h-4 w-full" />
            <Skeleton class="h-10 w-full" />
        </div>

        <template v-else-if="view === 'form' && owner">
            <h1 class="text-2xl font-semibold tracking-tight">Hola, {{ owner.name }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Elige una contraseña para entrar con <span class="font-medium text-foreground">{{ owner.email }}</span
                >.
            </p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <PasswordFields id="password" :min-length="minLength" v-model:password="password" v-model:confirmation="passwordConfirmation" />

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
            <CircleCheck class="size-8 text-action" aria-hidden="true" />
            <h1 class="mt-4 text-2xl font-semibold tracking-tight">Contraseña guardada</h1>
            <p class="mt-1 text-sm text-muted-foreground">Ya puedes iniciar sesión con tu correo y tu nueva contraseña.</p>
            <Button as-child class="mt-8 w-full">
                <RouterLink :to="{ name: loginRoute }">Ir a iniciar sesión</RouterLink>
            </Button>
        </template>

        <template v-else-if="view === 'invalid'">
            <h1 class="text-2xl font-semibold tracking-tight">Este enlace ya no es válido</h1>
            <p role="alert" class="mt-1 text-sm text-muted-foreground">{{ invalidHelp }}</p>
            <template v-if="forgotRoute">
                <Button as-child class="mt-8 w-full">
                    <RouterLink :to="{ name: forgotRoute }">Pedir un enlace nuevo</RouterLink>
                </Button>
                <p class="mt-4 text-center text-sm">
                    <RouterLink :to="{ name: loginRoute }" class="text-muted-foreground transition-colors hover:text-action">Ir a iniciar sesión</RouterLink>
                </p>
            </template>
            <Button v-else as-child variant="outline" class="mt-8 w-full">
                <RouterLink :to="{ name: loginRoute }">Ir a iniciar sesión</RouterLink>
            </Button>
        </template>

        <template v-else>
            <h1 class="text-2xl font-semibold tracking-tight">No pudimos comprobar el enlace</h1>
            <p role="alert" class="mt-1 text-sm text-muted-foreground">Recarga la página para intentarlo de nuevo.</p>
        </template>
    </AuthLayout>
</template>
