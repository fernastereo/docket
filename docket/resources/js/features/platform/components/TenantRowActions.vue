<script setup lang="ts">
import { computed } from 'vue';
import { KeyRound, Loader2, Send } from 'lucide-vue-next';
import { IconButton } from '@/components/ui/icon-button';
import type { Tenant } from '@/features/platform/types';

/**
 * Las dos formas de darle acceso al administrador de una curaduría: enviarle su
 * enlace (invitación, o recuperación si ya tiene contraseña) o fijarle la
 * contraseña. Solo presenta; las acciones las ejecuta quien lo usa.
 */
const props = defineProps<{
    tenant: Tenant;
    /** Hay un envío de enlace en curso para esta curaduría. */
    sending: boolean;
}>();
defineEmits<{ sendLink: []; changePassword: [] }>();

const admin = computed(() => props.tenant.admin);
const canChangePassword = computed(() => ['demo', 'active'].includes(props.tenant.status));
const linkLabel = computed(() => (admin.value?.invitation_pending ? 'Reenviar invitación' : 'Enviar enlace de recuperación'));
</script>

<template>
    <div v-if="admin" class="flex items-center justify-end gap-1">
        <IconButton
            label="Cambiar contraseña"
            :aria-label="`Cambiar la contraseña de ${admin.email}`"
            :disabled="!canChangePassword"
            disabled-reason="Solo se puede cambiar en una curaduría activa o en demo."
            @click="$emit('changePassword')"
        >
            <KeyRound aria-hidden="true" />
        </IconButton>
        <IconButton :label="linkLabel" :aria-label="`${linkLabel} a ${admin.email}`" :disabled="sending" @click="$emit('sendLink')">
            <Loader2 v-if="sending" class="animate-spin" aria-hidden="true" />
            <Send v-else aria-hidden="true" />
        </IconButton>
    </div>
</template>
