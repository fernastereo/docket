<script setup lang="ts">
import LoginScreen from '@/features/auth/components/LoginScreen.vue';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';

const auth = usePlatformAuthStore();
</script>

<template>
    <LoginScreen
        :auth="auth"
        home-route="platform.tenants"
        subtitle="Usa tu cuenta del panel de plataforma."
        aside-note="Panel de plataforma · acceso para el equipo de CuraduriAPP."
    />
</template>
