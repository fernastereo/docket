<script setup lang="ts">
import { useRoute } from 'vue-router';
import ChoosePasswordScreen from '@/features/auth/components/ChoosePasswordScreen.vue';
import { readAppContext } from '@/lib/appContext';
import { EMPLOYEE_MIN_PASSWORD_LENGTH } from '@/lib/password';

/**
 * Enlace del correo de invitación o de recuperación (público, en el subdominio de la
 * curaduría): los dos usan el mismo token y la misma pantalla.
 */
const route = useRoute();
const { tenantName } = readAppContext();
const url = `/employee/invitation/${encodeURIComponent(String(route.params.token))}`;
</script>

<template>
    <ChoosePasswordScreen
        :show-url="url"
        :submit-url="`${url}/accept`"
        login-route="employee.login"
        forgot-route="employee.forgot"
        :aside-title="tenantName ?? 'Tu curaduría'"
        :min-length="EMPLOYEE_MIN_PASSWORD_LENGTH"
        invalid-help="Pudo vencer o ya se usó. Pide uno nuevo desde «¿Olvidaste tu contraseña?», o pídele ayuda al administrador de tu curaduría."
    />
</template>
