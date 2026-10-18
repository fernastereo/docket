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
import StatusBadge from '@/features/platform/components/StatusBadge.vue';
import { useTenantsStore } from '@/features/platform/stores/tenants';

const tenants = useTenantsStore();

const dialogOpen = ref(false);
const form = reactive({ name: '', subdomain: '', active: false });
const submitting = ref(false);
const fieldErrors = ref<Record<string, string[]>>({});
const formError = ref('');
const loadError = ref(false);

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
        toast.success('Curaduría creada', { description: tenant.host ?? tenant.name });
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
                        <TableCell><StatusBadge :status="t.status" /></TableCell>
                        <TableCell class="text-muted-foreground tabular-nums">{{ dateFormat.format(new Date(t.created_at)) }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </Card>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
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
    </div>
</template>
