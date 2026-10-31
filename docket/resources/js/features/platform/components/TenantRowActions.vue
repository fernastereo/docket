<script setup lang="ts">
import { computed } from 'vue';
import { KeyRound, Loader2, Send, ShieldOff, UserPlus } from 'lucide-vue-next';
import { IconButton } from '@/components/ui/icon-button';
import type { Tenant } from '@/features/platform/types';

/**
 * Lo que el operador puede hacer con el acceso del administrador de una
 * curaduría: asignarlo si falta, o —si existe— reiniciar su verificación en dos
 * pasos, fijarle la contraseña o enviarle su enlace (invitación, o recuperación
 * si ya tiene contraseña). Solo presenta; las acciones las ejecuta quien lo usa.
 */
const props = defineProps<{
    tenant: Tenant;
    /** Hay un envío de enlace en curso para esta curaduría. */
    sending: boolean;
}>();
defineEmits<{ sendLink: []; changePassword: []; assignAdmin: []; resetMfa: [] }>();

const admin = computed(() => props.tenant.admin);
const writable = computed(() => ['demo', 'active'].includes(props.tenant.status));
const accessible = computed(() => writable.value || props.tenant.status === 'suspended');
const linkLabel = computed(() => (admin.value?.invitation_pending ? 'Reenviar invitación' : 'Enviar enlace de recuperación'));
const readOnlyReason = 'Solo se puede en una curaduría activa o en demo.';
</script>

<template>
    <div v-if="admin" class="flex items-center justify-end gap-1">
        <IconButton
            v-if="admin.mfa_enabled"
            label="Reiniciar verificación en dos pasos"
            :aria-label="`Reiniciar la verificación en dos pasos de ${admin.email}`"
            :disabled="!writable"
            :disabled-reason="readOnlyReason"
            @click="$emit('resetMfa')"
        >
            <ShieldOff aria-hidden="true" />
        </IconButton>
        <IconButton
            label="Cambiar contraseña"
            :aria-label="`Cambiar la contraseña de ${admin.email}`"
            :disabled="!writable"
            :disabled-reason="readOnlyReason"
            @click="$emit('changePassword')"
        >
            <KeyRound aria-hidden="true" />
        </IconButton>
        <IconButton :label="linkLabel" :aria-label="`${linkLabel} a ${admin.email}`" :disabled="sending" @click="$emit('sendLink')">
            <Loader2 v-if="sending" class="animate-spin" aria-hidden="true" />
            <Send v-else aria-hidden="true" />
        </IconButton>
    </div>
    <div v-else-if="accessible" class="flex items-center justify-end">
        <IconButton
            label="Asignar administrador"
            :aria-label="`Asignar administrador a ${tenant.name}`"
            :disabled="!writable"
            :disabled-reason="readOnlyReason"
            @click="$emit('assignAdmin')"
        >
            <UserPlus aria-hidden="true" />
        </IconButton>
    </div>
</template>
