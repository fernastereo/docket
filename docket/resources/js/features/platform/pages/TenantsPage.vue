<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { ExternalLink, KeyRound, Landmark, Loader2, Plus, Send } from 'lucide-vue-next';
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
import StatusBadge from '@/features/platform/components/StatusBadge.vue';
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

const dateFormat = new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium' });

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

const passwordTarget = ref<Tenant | null>(null);
const passwordForm = reactive({ password: '', confirmation: '' });
const passwordSubmitting = ref(false);
const passwordErrors = ref<string[]>([]);

function openPassword(tenant: Tenant): void {
    passwordTarget.value = tenant;
    passwordForm.password = '';
    passwordForm.confirmation = '';
    passwordErrors.value = [];
}

async function submitPassword(): Promise<void> {
    if (!passwordTarget.value) return;

    passwordSubmitting.value = true;
    passwordErrors.value = [];
    try {
        await tenants.setAdminPassword(passwordTarget.value.id, passwordForm.password, passwordForm.confirmation);
        toast.success('Contraseña cambiada', { description: `Ya puede entrar ${passwordTarget.value.admin?.email ?? ''} con la contraseña nueva.` });
        passwordTarget.value = null;
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        passwordErrors.value = Object.values(e.errors ?? {})[0] ?? [e.message];
    } finally {
        passwordForm.password = '';
        passwordForm.confirmation = '';
        passwordSubmitting.value = false;
    }
}

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
                    <TableRow class="hover:bg-transparent">
                        <TableHead>Nombre</TableHead>
                        <TableHead>Dirección</TableHead>
                        <TableHead>Administrador</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead>Creada</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="t in tenants.items" :key="t.id">
                        <TableCell>
                            <span class="font-medium">{{ t.name }}</span>
                            <span v-if="t.failure_reason" class="mt-0.5 block max-w-md truncate text-xs text-destructive">{{ t.failure_reason }}</span>
                        </TableCell>
                        <TableCell>
                            <a
                                v-if="t.host"
                                :href="tenantUrl(t.host)"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1.5 text-primary underline-offset-4 hover:underline"
                            >
                                {{ t.host }}
                                <ExternalLink class="size-3.5" aria-hidden="true" />
                            </a>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell>
                            <div v-if="t.admin" class="space-y-2">
                                <div class="min-w-0">
                                    <span class="block truncate">{{ t.admin.name }}</span>
                                    <span class="block truncate text-xs text-muted-foreground">{{ t.admin.email }}</span>
                                    <span v-if="t.admin.invitation_pending" class="text-xs font-medium text-warning">Invitación pendiente</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :disabled="!['demo', 'active'].includes(t.status)"
                                        :aria-label="`Cambiar la contraseña de ${t.admin.email}`"
                                        @click="openPassword(t)"
                                    >
                                        <KeyRound aria-hidden="true" />
                                        Cambiar contraseña
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :disabled="sending === t.id"
                                        :aria-label="`${t.admin.invitation_pending ? 'Reenviar invitación' : 'Enviar enlace de recuperación'} a ${t.admin.email}`"
                                        @click="sendLink(t)"
                                    >
                                        <Loader2 v-if="sending === t.id" class="animate-spin" aria-hidden="true" />
                                        <Send v-else aria-hidden="true" />
                                        {{ t.admin.invitation_pending ? 'Reenviar invitación' : 'Enviar enlace' }}
                                    </Button>
                                </div>
                            </div>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell><StatusBadge :status="t.status" /></TableCell>
                        <TableCell class="text-muted-foreground tabular-nums">{{ dateFormat.format(new Date(t.created_at)) }}</TableCell>
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

        <Dialog :open="passwordTarget !== null" @update:open="(open) => !open && (passwordTarget = null)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cambiar contraseña</DialogTitle>
                    <DialogDescription>
                        Para {{ passwordTarget?.admin?.name }} ({{ passwordTarget?.admin?.email }}). No se envía por correo: se la comunicas tú. Su verificación en dos pasos
                        se mantiene.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-5" @submit.prevent="submitPassword">
                    <div class="space-y-2">
                        <Label for="admin_password">Contraseña nueva</Label>
                        <Input id="admin_password" v-model="passwordForm.password" type="password" autocomplete="new-password" required minlength="12" />
                        <p class="text-xs text-muted-foreground">Al menos 12 caracteres.</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="admin_password_confirmation">Repite la contraseña</Label>
                        <Input id="admin_password_confirmation" v-model="passwordForm.confirmation" type="password" autocomplete="new-password" required />
                    </div>

                    <p v-for="m in passwordErrors" :key="m" role="alert" class="text-sm text-destructive">{{ m }}</p>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="passwordTarget = null">Cancelar</Button>
                        <Button type="submit" :disabled="passwordSubmitting">
                            <Loader2 v-if="passwordSubmitting" class="animate-spin" aria-hidden="true" />
                            {{ passwordSubmitting ? 'Guardando…' : 'Cambiar contraseña' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
