<script setup lang="ts">
import MfaChallenge from '@/features/auth/components/MfaChallenge.vue';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';

const auth = usePlatformAuthStore();
</script>

<template>
    <MfaChallenge
        :auth="auth"
        login-route="platform.login"
        home-route="platform.tenants"
        required-text="Es obligatoria para las cuentas de plataforma."
    />
</template>
