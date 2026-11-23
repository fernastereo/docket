<script setup lang="ts">
import ForgotPasswordScreen from '@/features/auth/components/ForgotPasswordScreen.vue';
</script>

<template>
    <ForgotPasswordScreen endpoint="/platform/forgot-password" login-route="platform.login" aside-title="Panel de plataforma" account-place="el panel de plataforma" />
</template>
