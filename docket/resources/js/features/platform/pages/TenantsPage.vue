<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { ExternalLink, Landmark, Loader2, Plus } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { ApiError } from '@/api/client';
import EmptyState from '@/components/layout/EmptyState.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AdminPasswordDialog from '@/features/platform/components/AdminPasswordDialog.vue';
import AssignAdminDialog from '@/features/platform/components/AssignAdminDialog.vue';
import ResetAdminMfaDialog from '@/features/platform/components/ResetAdminMfaDialog.vue';
import StatusBadge from '@/features/platform/components/StatusBadge.vue';
import TenantAdminCell from '@/features/platform/components/TenantAdminCell.vue';
import TenantRowActions from '@/features/platform/components/TenantRowActions.vue';
import { useTenantsStore } from '@/features/platform/stores/tenants';
import type { Tenant } from '@/features/platform/types';

const tenants = useTenantsStore();

const dialogOpen = ref(false);
const form = reactive({ name: '', subdomain: '', active: false, admin_name: '', admin_email: '' });
const submitting = ref(false);
const fieldErrors = ref<Record<string, string[]>>({});
const formError = ref('');
const loadError = ref(false);
const sending = ref<string | null>(null);

const dateFormat = new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short', year: 'numeric' });

/** "30 oct 2026": el formato corto de es-CO sobra de "de" y de punto. */
function formatDate(iso: string): string {
    const part = (type: string) => dateFormat.formatToParts(new Date(iso)).find((p) => p.type === type)?.value.replace('.', '') ?? '';

    return `${part('day')} ${part('month')} ${part('year')}`;
}

onMounted(async () => {
    try {
        await tenants.load();
    } catch {
        loadError.value = true;
    }
});

function openDialog(): void {
    fieldErrors.value = {};
    formError.value = '';
    dialogOpen.value = true;
}

async function submit(): Promise<void> {
    submitting.value = true;
    fieldErrors.value = {};
    formError.value = '';

    try {
        const tenant = await tenants.create({ ...form });
        dialogOpen.value = false;
        toast.success('Curaduría creada', { description: `La invitación llegará a ${form.admin_email}.` });
        form.name = '';
        form.subdomain = '';
        form.active = false;
        form.admin_name = '';
        form.admin_email = '';
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

async function sendLink(tenant: Tenant): Promise<void> {
    if (!tenant.admin) return;

    const pending = tenant.admin.invitation_pending;
    sending.value = tenant.id;
    try {
        await tenants.sendAdminLink(tenant.id);
        toast.success(pending ? 'Invitación enviada' : 'Enlace de recuperación enviado', {
            description: `Llegará a ${tenant.admin.email}. El enlace anterior ya no sirve.`,
        });
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        toast.error('No se pudo enviar el enlace', { description: Object.values(e.errors ?? {})[0]?.[0] ?? e.message });
    } finally {
        sending.value = null;
    }
}

/** Curaduría sobre la que hay un diálogo abierto: cambio de contraseña, asignar administrador o reiniciar su MFA. */
const passwordTarget = ref<Tenant | null>(null);
const assignTarget = ref<Tenant | null>(null);
const mfaTarget = ref<Tenant | null>(null);

/** Enlace al sitio de la curaduría, conservando el puerto del panel (8443 en local). */
function tenantUrl(host: string): string {
    const port = window.location.port ? `:${window.location.port}` : '';

    return `https://${host}${port}`;
}
</script>

<template>
    <div class="space-y-8">
        <PageHeader title="Curadurías" description="Cada curaduría tiene su propia base de datos y su propia dirección web.">
            <template #actions>
                <Button @click="openDialog">
                    <Plus />
                    Nueva curaduría
                </Button>
            </template>
        </PageHeader>

        <Card class="gap-0 overflow-hidden py-0">
            <div v-if="tenants.loading" class="space-y-3 p-6" aria-busy="true" aria-label="Cargando curadurías">
                <Skeleton v-for="n in 3" :key="n" class="h-9 w-full" />
            </div>

            <p v-else-if="loadError" role="alert" class="p-6 text-sm text-destructive">
                No se pudo cargar la lista. Recarga la página para intentarlo de nuevo.
            </p>

            <EmptyState
                v-else-if="tenants.items.length === 0"
                :icon="Landmark"
                title="Todavía no hay curadurías"
                description="Crea la primera para probar el flujo completo: su base de datos, su dirección web y su acceso."
            >
                <Button @click="openDialog">
                    <Plus />
                    Nueva curaduría
                </Button>
            </EmptyState>

            <Table v-else>
                <TableHeader>
                    <TableRow class="bg-muted/40 hover:bg-muted/40">
                        <TableHead class="pl-6">Curaduría</TableHead>
                        <TableHead>Administrador</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead>Creada</TableHead>
                        <TableHead class="pr-6 text-right"><span class="sr-only">Acciones</span></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="t in tenants.items" :key="t.id" class="last:border-b-0">
                        <TableCell class="py-4 pl-6">
                            <span class="block font-medium">{{ t.name }}</span>
                            <a
                                v-if="t.host"
                                :href="tenantUrl(t.host)"
                                target="_blank"
                                rel="noopener"
                                class="mt-0.5 inline-flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-action"
                            >
                                {{ t.host }}
                                <ExternalLink class="size-3" aria-hidden="true" />
                            </a>
                            <span v-if="t.failure_reason" class="mt-0.5 block max-w-md truncate text-xs text-destructive">{{ t.failure_reason }}</span>
                        </TableCell>
                        <TableCell class="py-4"><TenantAdminCell :admin="t.admin" /></TableCell>
                        <TableCell class="py-4"><StatusBadge :status="t.status" /></TableCell>
                        <TableCell class="py-4 text-muted-foreground tabular-nums">{{ formatDate(t.created_at) }}</TableCell>
                        <TableCell class="py-4 pr-6">
                            <TenantRowActions
                                :tenant="t"
                                :sending="sending === t.id"
                                @send-link="sendLink(t)"
                                @change-password="passwordTarget = t"
                                @assign-admin="assignTarget = t"
                                @reset-mfa="mfaTarget = t"
                            />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </Card>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Nueva curaduría</DialogTitle>
                    <DialogDescription>Se crea su base de datos y su dirección web. Puede tardar unos segundos.</DialogDescription>
                </DialogHeader>

                <form class="space-y-5" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="name">Nombre</Label>
                        <Input id="name" v-model="form.name" required :aria-invalid="!!fieldErrors.name" />
                        <p v-for="m in fieldErrors.name" :key="m" class="text-sm text-destructive">{{ m }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="subdomain">Subdominio</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                id="subdomain"
                                v-model="form.subdomain"
                                required
                                autocapitalize="none"
                                autocomplete="off"
                                :aria-invalid="!!fieldErrors.subdomain"
                            />
                            <span class="shrink-0 text-sm text-muted-foreground">.{{ tenants.baseDomain }}</span>
                        </div>
                        <p v-for="m in fieldErrors.subdomain" :key="m" class="text-sm text-destructive">{{ m }}</p>
                    </div>

                    <fieldset class="space-y-4 rounded-lg border p-4">
                        <legend class="px-1 text-sm font-medium">Primer administrador</legend>
                        <p class="text-sm text-muted-foreground">Recibe un correo para elegir su contraseña y crear al resto del personal.</p>
                        <div class="space-y-2">
                            <Label for="admin_name">Nombre</Label>
                            <Input id="admin_name" v-model="form.admin_name" required autocomplete="off" :aria-invalid="!!fieldErrors.admin_name" />
                            <p v-for="m in fieldErrors.admin_name" :key="m" class="text-sm text-destructive">{{ m }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="admin_email">Correo</Label>
                            <Input
                                id="admin_email"
                                v-model="form.admin_email"
                                type="email"
                                required
                                autocomplete="off"
                                :aria-invalid="!!fieldErrors.admin_email"
                            />
                            <p v-for="m in fieldErrors.admin_email" :key="m" class="text-sm text-destructive">{{ m }}</p>
                        </div>
                    </fieldset>

                    <label class="flex items-center gap-2.5 text-sm">
                        <input v-model="form.active" type="checkbox" class="size-4 rounded border-input accent-primary" />
                        Crearla como activa (si no, queda en demo)
                    </label>

                    <p v-if="formError" role="alert" class="text-sm text-destructive">{{ formError }}</p>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogOpen = false">Cancelar</Button>
                        <Button type="submit" :disabled="submitting">
                            <Loader2 v-if="submitting" class="animate-spin" aria-hidden="true" />
                            {{ submitting ? 'Creando…' : 'Crear curaduría' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AdminPasswordDialog :tenant="passwordTarget" @close="passwordTarget = null" />
        <AssignAdminDialog :tenant="assignTarget" @close="assignTarget = null" />
        <ResetAdminMfaDialog :tenant="mfaTarget" @close="mfaTarget = null" />
    </div>
</template>
