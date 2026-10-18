<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { ApiError } from '@/api/client';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';
import { useTenantsStore } from '@/features/platform/stores/tenants';
import type { TenantStatus } from '@/features/platform/types';

const auth = usePlatformAuthStore();
const tenants = useTenantsStore();
const router = useRouter();

const form = reactive({ name: '', subdomain: '', active: false });
const submitting = ref(false);
const fieldErrors = ref<Record<string, string[]>>({});
const formError = ref('');
const created = ref('');

const statusLabels: Record<TenantStatus, string> = {
    provisioning: 'Creando',
    failed: 'Fallida',
    demo: 'Demo',
    active: 'Activa',
    suspended: 'Suspendida',
    terminated: 'Terminada',
};

onMounted(() => tenants.load());

async function submit(): Promise<void> {
    submitting.value = true;
    fieldErrors.value = {};
    formError.value = '';
    created.value = '';

    try {
        const tenant = await tenants.create({ ...form });
        created.value = tenant.host ?? tenant.name;
        form.name = '';
        form.subdomain = '';
        form.active = false;
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        if (e.status === 422) {
            fieldErrors.value = e.errors ?? {};
        } else {
            formError.value = e.message;
        }
    } finally {
        submitting.value = false;
    }
}

async function logout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'platform.login' });
}

/** Enlace al sitio de la curaduría, conservando el puerto del panel (8443 en local). */
function tenantUrl(host: string): string {
    const port = window.location.port ? `:${window.location.port}` : '';

    return `https://${host}${port}`;
}
</script>

<template>
    <div class="mx-auto max-w-4xl p-6">
        <header class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Curadurías</h1>
                <p class="text-sm text-neutral-500">{{ auth.user?.name }} · {{ auth.user?.email }}</p>
            </div>
            <button class="rounded border border-neutral-300 px-3 py-1.5 text-sm" @click="logout">Salir</button>
        </header>

        <section class="mt-8">
            <h2 class="text-lg font-medium">Nueva curaduría</h2>
            <form class="mt-3 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                <div>
                    <label for="name" class="block text-sm font-medium">Nombre</label>
                    <input
                        id="name"
                        v-model="form.name"
                        required
                        class="mt-1 w-full rounded border border-neutral-300 px-3 py-2"
                    />
                    <p v-for="m in fieldErrors.name" :key="m" class="mt-1 text-sm text-red-700">{{ m }}</p>
                </div>
                <div>
                    <label for="subdomain" class="block text-sm font-medium">Subdominio</label>
                    <input
                        id="subdomain"
                        v-model="form.subdomain"
                        required
                        autocapitalize="none"
                        class="mt-1 w-full rounded border border-neutral-300 px-3 py-2"
                    />
                    <p class="mt-1 text-xs text-neutral-500">
                        Se publicará en {{ form.subdomain || 'subdominio' }}.{{ tenants.baseDomain }}
                    </p>
                    <p v-for="m in fieldErrors.subdomain" :key="m" class="mt-1 text-sm text-red-700">{{ m }}</p>
                </div>
                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input v-model="form.active" type="checkbox" />
                    Crear como activa (si no, queda en demo)
                </label>

                <p v-if="formError" role="alert" class="text-sm text-red-700 sm:col-span-2">{{ formError }}</p>
                <p v-if="created" role="status" class="text-sm text-green-700 sm:col-span-2">Curaduría creada: {{ created }}</p>

                <div class="sm:col-span-2">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="rounded bg-neutral-900 px-4 py-2 font-medium text-white disabled:opacity-50"
                    >
                        {{ submitting ? 'Creando… (puede tardar unos segundos)' : 'Crear curaduría' }}
                    </button>
                </div>
            </form>
        </section>

        <section class="mt-10">
            <h2 class="text-lg font-medium">Existentes</h2>
            <p v-if="tenants.loading" class="mt-3 text-sm text-neutral-500">Cargando…</p>
            <p v-else-if="tenants.items.length === 0" class="mt-3 text-sm text-neutral-500">Todavía no hay curadurías.</p>
            <table v-else class="mt-3 w-full text-left text-sm">
                <thead class="border-b border-neutral-300 text-neutral-500">
                    <tr>
                        <th class="py-2 font-medium">Nombre</th>
                        <th class="py-2 font-medium">Sitio</th>
                        <th class="py-2 font-medium">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in tenants.items" :key="t.id" class="border-b border-neutral-200">
                        <td class="py-2">{{ t.name }}</td>
                        <td class="py-2">
                            <a v-if="t.host" :href="tenantUrl(t.host)" class="text-blue-700 underline" target="_blank" rel="noopener">{{ t.host }}</a>
                            <span v-else class="text-neutral-400">—</span>
                        </td>
                        <td class="py-2">
                            {{ statusLabels[t.status] }}
                            <span v-if="t.failure_reason" class="block text-xs text-red-700">{{ t.failure_reason }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
