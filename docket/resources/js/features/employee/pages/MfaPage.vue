<script setup lang="ts">
import MfaChallenge from '@/features/auth/components/MfaChallenge.vue';
import { useEmployeeAuthStore } from '@/features/employee/stores/auth';

const auth = useEmployeeAuthStore();
</script>

<template>
    <MfaChallenge
        :auth="auth"
        login-route="employee.login"
        home-route="employee.home"
        required-text="Tu rol en la curaduría exige este paso."
    />
</template>
