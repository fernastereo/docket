<script setup lang="ts">
import { Inbox } from 'lucide-vue-next';
import EmptyState from '@/components/layout/EmptyState.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Card } from '@/components/ui/card';
import { useEmployeeAuthStore } from '@/features/employee/stores/auth';

const auth = useEmployeeAuthStore();
</script>

<template>
    <div class="space-y-6">
        <PageHeader
            title="Inicio"
            :description="auth.user ? `${auth.user.name} · ${auth.user.role_labels.join(', ')}` : undefined"
        />
        <Card>
            <EmptyState
                :icon="Inbox"
                title="Aquí aparecerán tus trámites"
                description="Tu sesión ya está protegida. Los expedientes y las tareas llegan en las próximas versiones."
            />
        </Card>
    </div>
</template>
