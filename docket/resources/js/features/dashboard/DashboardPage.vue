<script setup lang="ts">
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

onMounted(() => {
    auth.fetchUser();
});
</script>

<template>
    <div class="mx-auto max-w-3xl p-8">
        <h1 class="text-2xl font-semibold">CuraduriAPP</h1>
        <p class="mt-2 text-neutral-600">
            Esqueleto vivo — Laravel + stancl/tenancy + Vue 3 / TypeScript.
        </p>
        <p class="mt-4 text-sm text-neutral-500" v-if="auth.loaded">
            {{ auth.user ? `Sesión: ${auth.user.name}` : 'Sin sesión activa.' }}
        </p>
    </div>
</template>
