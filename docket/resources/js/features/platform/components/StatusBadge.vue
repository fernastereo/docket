<script setup lang="ts">
import { computed } from 'vue';
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import type { TenantStatus } from '@/features/platform/types';

const props = defineProps<{ status: TenantStatus }>();

// Un estado, una palabra y un tono. La etiqueta usa el vocabulario del ciclo de
// vida del tenant (ADR-010), no el nombre interno del enum.
const presentation: Record<TenantStatus, { label: string; variant: BadgeVariants['variant'] }> = {
    provisioning: { label: 'Creando', variant: 'info' },
    failed: { label: 'Fallida', variant: 'destructive' },
    demo: { label: 'Demo', variant: 'secondary' },
    active: { label: 'Activa', variant: 'success' },
    suspended: { label: 'Suspendida', variant: 'warning' },
    terminated: { label: 'Terminada', variant: 'outline' },
};

const current = computed(() => presentation[props.status]);
</script>

<template>
    <Badge :variant="current.variant">{{ current.label }}</Badge>
</template>
