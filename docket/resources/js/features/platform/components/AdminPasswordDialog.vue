<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { Loader2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { ApiError } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import PasswordFields from '@/features/auth/components/PasswordFields.vue';
import { useTenantsStore } from '@/features/platform/stores/tenants';
import type { Tenant } from '@/features/platform/types';

/**
 * El operador fija la contraseña del administrador de una curaduría. Está abierto
 * mientras haya una curaduría elegida (`tenant`); al terminar o cancelar avisa
 * con `close`. La contraseña no se envía por correo: la comunica el operador.
 */
const props = defineProps<{ tenant: Tenant | null }>();
const emit = defineEmits<{ close: [] }>();

const tenants = useTenantsStore();
const form = reactive({ password: '', confirmation: '' });
const submitting = ref(false);
const errors = ref<string[]>([]);

// Cada vez que se abre para una curaduría, arranca limpio.
watch(
    () => props.tenant?.id,
    () => {
        form.password = '';
        form.confirmation = '';
        errors.value = [];
    },
);

async function submit(): Promise<void> {
    if (!props.tenant) return;

    submitting.value = true;
    errors.value = [];
    try {
        await tenants.setAdminPassword(props.tenant.id, form.password, form.confirmation);
        toast.success('Contraseña cambiada', { description: `Ya puede entrar ${props.tenant.admin?.email ?? ''} con la contraseña nueva.` });
        emit('close');
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        errors.value = Object.values(e.errors ?? {})[0] ?? [e.message];
    } finally {
        form.password = '';
        form.confirmation = '';
        submitting.value = false;
    }
}
</script>

<template>
    <Dialog :open="tenant !== null" @update:open="(open) => !open && emit('close')">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Cambiar contraseña</DialogTitle>
                <DialogDescription>
                    Para {{ tenant?.admin?.name }} ({{ tenant?.admin?.email }}). No se envía por correo: se la comunicas tú. Su verificación en dos pasos se mantiene.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-5" @submit.prevent="submit">
                <PasswordFields id="admin_password" v-model:password="form.password" v-model:confirmation="form.confirmation" />

                <p v-for="m in errors" :key="m" role="alert" class="text-sm text-destructive">{{ m }}</p>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('close')">Cancelar</Button>
                    <Button type="submit" :disabled="submitting">
                        <Loader2 v-if="submitting" class="animate-spin" aria-hidden="true" />
                        {{ submitting ? 'Guardando…' : 'Cambiar contraseña' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
