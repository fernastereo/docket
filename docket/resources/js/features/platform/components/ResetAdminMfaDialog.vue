<script setup lang="ts">
import { ref, watch } from 'vue';
import { Loader2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { ApiError } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useTenantsStore } from '@/features/platform/stores/tenants';
import type { Tenant } from '@/features/platform/types';

/**
 * Confirma el reinicio de la verificación en dos pasos del administrador de una
 * curaduría. Pide confirmación porque quita una protección de la cuenta: en su
 * próximo acceso la persona tendrá que activarla de nuevo. Está abierto mientras
 * haya una curaduría elegida (`tenant`); avisa con `close` al terminar o cancelar.
 */
const props = defineProps<{ tenant: Tenant | null }>();
const emit = defineEmits<{ close: [] }>();

const tenants = useTenantsStore();
const submitting = ref(false);
const error = ref('');

watch(
    () => props.tenant?.id,
    () => {
        error.value = '';
    },
);

async function confirm(): Promise<void> {
    if (!props.tenant) return;

    submitting.value = true;
    error.value = '';
    try {
        await tenants.resetAdminMfa(props.tenant.id);
        toast.success('Verificación en dos pasos reiniciada', { description: `${props.tenant.admin?.email ?? ''} deberá activarla de nuevo al entrar.` });
        emit('close');
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        error.value = Object.values(e.errors ?? {})[0]?.[0] ?? e.message;
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Dialog :open="tenant !== null" @update:open="(open) => !open && emit('close')">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Reiniciar la verificación en dos pasos</DialogTitle>
                <DialogDescription>
                    {{ tenant?.admin?.name }} ({{ tenant?.admin?.email }}) perderá su aplicación de autenticación registrada y sus códigos de recuperación. En su próximo
                    acceso tendrá que activarla de nuevo. Su contraseña no cambia.
                </DialogDescription>
            </DialogHeader>

            <p v-if="error" role="alert" class="text-sm text-destructive">{{ error }}</p>

            <DialogFooter>
                <Button type="button" variant="outline" @click="emit('close')">Cancelar</Button>
                <Button type="button" variant="destructive" :disabled="submitting" @click="confirm">
                    <Loader2 v-if="submitting" class="animate-spin" aria-hidden="true" />
                    {{ submitting ? 'Reiniciando…' : 'Reiniciar' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
