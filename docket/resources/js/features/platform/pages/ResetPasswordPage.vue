<script setup lang="ts">
import { useRoute } from 'vue-router';
import ChoosePasswordScreen from '@/features/auth/components/ChoosePasswordScreen.vue';
import { PLATFORM_MIN_PASSWORD_LENGTH } from '@/lib/password';

/** Enlace del correo de recuperación del panel de plataforma (público, dominio central). */
const route = useRoute();
const url = `/platform/reset-password/${encodeURIComponent(String(route.params.token))}`;
</script>

<template>
    <ChoosePasswordScreen
        :show-url="url"
        :submit-url="url"
        login-route="platform.login"
        forgot-route="platform.forgot"
        aside-title="Panel de plataforma"
        :min-length="PLATFORM_MIN_PASSWORD_LENGTH"
        invalid-help="El enlace dura 60 minutos y solo sirve una vez. Pide uno nuevo desde «¿Olvidaste tu contraseña?»."
    />
</template>
