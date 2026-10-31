<script setup lang="ts">
import InitialsAvatar from '@/components/ui/avatar/InitialsAvatar.vue';
import type { TenantAdmin } from '@/features/platform/types';

/** Quién administra la curaduría: avatar, nombre, correo y, si falta, que la invitación sigue pendiente. */
defineProps<{ admin: TenantAdmin | null }>();
</script>

<template>
    <div v-if="admin" class="flex items-center gap-3">
        <InitialsAvatar :name="admin.name" />
        <div class="min-w-0">
            <span class="block truncate font-medium">{{ admin.name }}</span>
            <span class="block truncate text-xs text-muted-foreground">{{ admin.email }}</span>
        </div>
        <span
            v-if="admin.invitation_pending"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-warning/15 px-2 py-0.5 text-xs font-medium text-warning"
        >
            <span class="size-1.5 rounded-full bg-current" aria-hidden="true" />
            Invitación pendiente
        </span>
    </div>
    <span v-else class="text-muted-foreground">Sin administrador</span>
</template>
