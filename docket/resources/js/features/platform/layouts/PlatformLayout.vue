<script setup lang="ts">
import { Building2 } from 'lucide-vue-next';
import { useRouter } from 'vue-router';
import AppShell from '@/components/layout/AppShell.vue';
import type { NavItem } from '@/components/layout/nav';
import { usePlatformAuthStore } from '@/features/platform/stores/auth';

const auth = usePlatformAuthStore();
const router = useRouter();

const items: NavItem[] = [{ label: 'Curadurías', to: { name: 'platform.tenants' }, icon: Building2 }];

async function logout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'platform.login' });
}
</script>

<template>
    <AppShell :items="items" :user="auth.user" @logout="logout">
        <router-view />
    </AppShell>
</template>
