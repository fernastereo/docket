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
        forgot-route="employee.forgot"
        :subtitle="`Entra a ${tenantName ?? 'tu curaduría'} con tu correo y tu contraseña.`"
        :aside-title="tenantName ?? 'Tu curaduría'"
        help="¿No puedes entrar? Pídele ayuda al administrador de tu curaduría."
    />
</template>
