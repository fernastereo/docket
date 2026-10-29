<script setup lang="ts">
import { House } from 'lucide-vue-next';
import { useRouter } from 'vue-router';
import AppShell from '@/components/layout/AppShell.vue';
import type { NavItem } from '@/components/layout/nav';
import { useEmployeeAuthStore } from '@/features/employee/stores/auth';

const auth = useEmployeeAuthStore();
const router = useRouter();

const items: NavItem[] = [{ label: 'Inicio', to: { name: 'employee.home' }, icon: House }];

async function logout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'employee.login' });
}
</script>

<template>
    <AppShell :items="items" :user="auth.user" @logout="logout">
        <router-view />
    </AppShell>
</template>
