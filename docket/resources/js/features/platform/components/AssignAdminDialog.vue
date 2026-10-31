<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { Loader2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { ApiError } from '@/api/client';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTenantsStore } from '@/features/platform/stores/tenants';
import type { Tenant } from '@/features/platform/types';

/**
 * Da su administrador a una curaduría que no lo tiene. Está abierto mientras haya
 * una curaduría elegida (`tenant`); al terminar o cancelar avisa con `close`. La
 * persona recibe por correo la invitación para elegir su contraseña.
 */
const props = defineProps<{ tenant: Tenant | null }>();
const emit = defineEmits<{ close: [] }>();

const tenants = useTenantsStore();
const form = reactive({ name: '', email: '' });
const submitting = ref(false);
const fieldErrors = ref<Record<string, string[]>>({});

// Cada vez que se abre para una curaduría, arranca limpio.
watch(
    () => props.tenant?.id,
    () => {
        form.name = '';
        form.email = '';
        fieldErrors.value = {};
    },
);

async function submit(): Promise<void> {
    if (!props.tenant) return;

    submitting.value = true;
    fieldErrors.value = {};
    try {
        await tenants.assignAdmin(props.tenant.id, form.name, form.email);
        toast.success('Administrador asignado', { description: `La invitación llegará a ${form.email}.` });
        emit('close');
    } catch (e) {
        if (!(e instanceof ApiError)) throw e;
        fieldErrors.value = e.status === 422 ? (e.errors ?? {}) : { tenant: [e.message] };
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Dialog :open="tenant !== null" @update:open="(open) => !open && emit('close')">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Asignar administrador</DialogTitle>
                <DialogDescription>
                    {{ tenant?.name }} todavía no tiene administrador. Recibirá un correo para elegir su contraseña y crear al resto del personal.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="assign_admin_name">Nombre</Label>
                    <Input id="assign_admin_name" v-model="form.name" required autocomplete="off" :aria-invalid="!!fieldErrors.admin_name" />
                    <p v-for="m in fieldErrors.admin_name" :key="m" class="text-sm text-destructive">{{ m }}</p>
                </div>
                <div class="space-y-2">
                    <Label for="assign_admin_email">Correo</Label>
                    <Input id="assign_admin_email" v-model="form.email" type="email" required autocomplete="off" :aria-invalid="!!fieldErrors.admin_email" />
                    <p v-for="m in fieldErrors.admin_email" :key="m" class="text-sm text-destructive">{{ m }}</p>
                </div>

                <p v-for="m in fieldErrors.tenant" :key="m" role="alert" class="text-sm text-destructive">{{ m }}</p>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('close')">Cancelar</Button>
                    <Button type="submit" :disabled="submitting">
                        <Loader2 v-if="submitting" class="animate-spin" aria-hidden="true" />
                        {{ submitting ? 'Asignando…' : 'Asignar administrador' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
