<script setup lang="ts">
import { ref } from 'vue';
import { CircleAlert, Loader2, MailCheck } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';
import { api, ApiError } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/features/auth/components/AuthLayout.vue';

/**
 * "¿Olvidaste tu contraseña?". Pide el correo y, pase lo que pase (exista la
 * cuenta o no), muestra la misma confirmación: la API tampoco dice si el correo
 * existe, y esta pantalla no lo adivina.
 */
const props = defineProps<{
    /** POST que pide el enlace (`/employee/forgot-password`, `/platform/forgot-password`). */
    endpoint: string;
    loginRoute: string;
    asideTitle: string;
    /** Dónde tendría la cuenta: "Curaduría Urbana 1", "el panel de plataforma". */
    accountPlace: string;
}>();

const sent = ref(false);
const email = ref('');
const submitting = ref(false);
const error = ref('');

async function submit(): Promise<void> {
    submitting.value = true;
    error.value = '';

    try {
        await api(props.endpoint, { method: 'POST', body: JSON.stringify({ email: email.value }) });
        sent.value = true;
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        error.value =
            e.status === 429
                ? 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.'
                : (e.errors?.email?.[0] ?? 'No se pudo enviar la solicitud. Inténtalo de nuevo.');
    } finally {
        submitting.value = false;
    }
}

function tryAnother(): void {
    sent.value = false;
    email.value = '';
}
</script>

<template>
    <AuthLayout :aside-title="asideTitle">
        <template v-if="!sent">
            <h1 class="text-2xl font-semibold tracking-tight">Recuperar contraseña</h1>
            <p class="mt-1 text-sm text-muted-foreground">Escribe tu correo y te enviamos un enlace para elegir una contraseña nueva.</p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="email">Correo</Label>
                    <Input id="email" v-model="email" type="email" autocomplete="username" required />
                </div>

                <p v-if="error" role="alert" class="flex items-start gap-2 text-sm text-destructive">
                    <CircleAlert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    {{ error }}
                </p>

                <Button type="submit" class="w-full" :disabled="submitting">
                    <Loader2 v-if="submitting" class="animate-spin" aria-hidden="true" />
                    {{ submitting ? 'Enviando…' : 'Enviar enlace' }}
                </Button>
            </form>

            <p class="mt-6 text-sm">
                <RouterLink :to="{ name: loginRoute }" class="text-muted-foreground transition-colors hover:text-action">Volver a iniciar sesión</RouterLink>
            </p>
        </template>

        <template v-else>
            <MailCheck class="size-8 text-action" aria-hidden="true" />
            <h1 class="mt-4 text-2xl font-semibold tracking-tight">Revisa tu correo</h1>
            <p role="status" class="mt-1 text-sm text-muted-foreground">
                Si {{ email }} tiene una cuenta en {{ accountPlace }}, te enviamos un enlace para elegir una contraseña nueva. Puede tardar un par de minutos; revisa también la
                carpeta de correo no deseado.
            </p>

            <Button as-child class="mt-8 w-full">
                <RouterLink :to="{ name: loginRoute }">Volver a iniciar sesión</RouterLink>
            </Button>
            <p class="mt-4 text-center text-sm">
                <button type="button" class="cursor-pointer text-muted-foreground transition-colors hover:text-action" @click="tryAnother">Usar otro correo</button>
            </p>
        </template>
    </AuthLayout>
</template>
