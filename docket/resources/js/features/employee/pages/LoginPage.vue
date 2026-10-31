<script setup lang="ts">
import LoginScreen from '@/features/auth/components/LoginScreen.vue';
import { useEmployeeAuthStore } from '@/features/employee/stores/auth';
import { readAppContext } from '@/lib/appContext';

const auth = useEmployeeAuthStore();
const { tenantName } = readAppContext();
</script>

<template>
    <LoginScreen
        :auth="auth"
        home-route="employee.home"
        subtitle="Usa tu cuenta de la curaduría."
        :aside-note="`${tenantName ?? 'Tu curaduría'} · acceso para el personal de la curaduría.`"
    />
</template>
