<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { CircleAlert, Loader2, ShieldCheck } from 'lucide-vue-next';
import { useRoute, useRouter } from 'vue-router';
import { ApiError } from '@/api/client';
import BrandMark from '@/components/layout/BrandMark.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';
import type { MfaSetup } from '@/features/platform/types';

/**
 * Segundo factor del panel. Tres vistas en una pantalla:
 * - setup: la cuenta aún no tiene MFA (es obligatoria) → QR + primer código.
 * - codes: recién activada → códigos de recuperación, que se muestran una vez.
 * - verify: la cuenta ya tiene MFA → código de la app (o de recuperación).
 */
const auth = usePlatformAuthStore();
const router = useRouter();
const route = useRoute();

const setup = ref<MfaSetup | null>(null);
const recoveryCodes = ref<string[]>([]);
const code = ref('');
const submitting = ref(false);
const error = ref('');

const view = computed<'setup' | 'codes' | 'verify'>(() => {
    if (recoveryCodes.value.length > 0) return 'codes';

    return auth.user?.mfa_enabled ? 'verify' : 'setup';
});

onMounted(async () => {
    if (view.value !== 'setup') return;

    try {
        setup.value = await auth.startMfaSetup();
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        error.value = 'No se pudo generar el código QR. Recarga la página para intentarlo de nuevo.';
    }
});

function messageFor(e: ApiError): string {
    if (e.status === 429) return 'Demasiados intentos fallidos. Espera unos minutos e inténtalo de nuevo.';

    return e.errors?.code?.[0] ?? e.errors?.mfa?.[0] ?? 'No se pudo validar el código.';
}

async function goToPanel(): Promise<void> {
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null;
    // Solo rutas internas: un `redirect=https://...` no debe sacar al usuario del sitio.
    await router.push(redirect && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : { name: 'platform.tenants' });
}

async function submit(): Promise<void> {
    submitting.value = true;
    error.value = '';

    try {
        if (view.value === 'setup') {
            recoveryCodes.value = await auth.confirmMfa(code.value);
            code.value = '';
        } else {
            await auth.verifyMfa(code.value);
            await goToPanel();
        }
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        error.value = messageFor(e);
        code.value = '';
    } finally {
        submitting.value = false;
    }
}

async function switchAccount(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'platform.login' });
}
</script>

<template>
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <div class="mb-10">
                <BrandMark />
            </div>

            <!-- Códigos de recuperación (una sola vez) -->
            <section v-if="view === 'codes'">
                <h1 class="flex items-center gap-2 text-2xl font-semibold tracking-tight">
                    <ShieldCheck class="size-6 text-primary" aria-hidden="true" />
                    Verificación en dos pasos activada
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Guarda estos códigos de recuperación en un lugar seguro, por ejemplo tu gestor de contraseñas. Cada uno sirve una sola vez si
                    pierdes acceso a tu app de autenticación. <strong>No se volverán a mostrar.</strong>
                </p>
                <ul class="mt-6 grid grid-cols-2 gap-2 rounded-lg border bg-card p-4 font-mono text-sm" aria-label="Códigos de recuperación">
                    <li v-for="item in recoveryCodes" :key="item">{{ item }}</li>
                </ul>
                <Button class="mt-6 w-full" @click="goToPanel">Ya los guardé, continuar</Button>
            </section>

            <!-- Activación -->
            <section v-else-if="view === 'setup'">
                <h1 class="text-2xl font-semibold tracking-tight">Activa la verificación en dos pasos</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Es obligatoria para las cuentas de plataforma. Escanea el código con una app de autenticación (Google Authenticator, 1Password,
                    Authy…) y escribe el código de 6 dígitos que muestre.
                </p>

                <div v-if="setup" class="mt-6 flex flex-col items-center gap-3 rounded-lg border bg-white p-4">
                    <img :src="setup.qr" alt="Código QR para la app de autenticación" class="size-48" width="192" height="192" />
                </div>
                <p v-if="setup" class="mt-3 text-xs text-muted-foreground">
                    ¿No puedes escanearlo? Escribe esta clave en la app:
                    <code class="font-mono break-all text-foreground">{{ setup.secret }}</code>
                </p>
                <p v-else-if="!error" class="mt-6 flex items-center gap-2 text-sm text-muted-foreground">
                    <Loader2 class="size-4 animate-spin" aria-hidden="true" /> Generando código…
                </p>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="code">Código de 6 dígitos</Label>
                        <Input id="code" v-model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required />
                    </div>
                    <p v-if="error" role="alert" class="flex items-start gap-2 text-sm text-destructive">
                        <CircleAlert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        {{ error }}
                    </p>
                    <Button type="submit" class="w-full" :disabled="submitting || !setup">
                        <Loader2 v-if="submitting" class="animate-spin" aria-hidden="true" />
                        {{ submitting ? 'Verificando…' : 'Activar' }}
                    </Button>
                </form>
            </section>

            <!-- Verificación en cada login -->
            <section v-else>
                <h1 class="text-2xl font-semibold tracking-tight">Verificación en dos pasos</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Escribe el código de 6 dígitos de tu app de autenticación. Si perdiste el acceso, usa uno de tus códigos de recuperación.
                </p>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="code">Código</Label>
                        <Input id="code" v-model="code" autocomplete="one-time-code" maxlength="32" required />
                    </div>
                    <p v-if="error" role="alert" class="flex items-start gap-2 text-sm text-destructive">
                        <CircleAlert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        {{ error }}
                    </p>
                    <Button type="submit" class="w-full" :disabled="submitting">
                        <Loader2 v-if="submitting" class="animate-spin" aria-hidden="true" />
                        {{ submitting ? 'Verificando…' : 'Verificar' }}
                    </Button>
                </form>
            </section>

            <p v-if="view !== 'codes'" class="mt-8 text-center text-sm text-muted-foreground">
                <button type="button" class="underline underline-offset-4 hover:text-foreground" @click="switchAccount">Usar otra cuenta</button>
            </p>
        </div>
    </main>
</template>
