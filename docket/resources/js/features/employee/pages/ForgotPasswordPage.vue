<script setup lang="ts">
import ForgotPasswordScreen from '@/features/auth/components/ForgotPasswordScreen.vue';
import { readAppContext } from '@/lib/appContext';

const { tenantName } = readAppContext();
</script>

<template>
    <ForgotPasswordScreen
        endpoint="/employee/forgot-password"
        login-route="employee.login"
        :aside-title="tenantName ?? 'Tu curaduría'"
        :account-place="tenantName ?? 'esta curaduría'"
    />
</template>
