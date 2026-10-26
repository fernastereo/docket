<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next';
import BrandMark from '@/components/layout/BrandMark.vue';
import { Button } from '@/components/ui/button';
</script>

<template>
    <div class="mx-auto flex min-h-screen max-w-md flex-col justify-center gap-6 px-6">
        <BrandMark />
        <p class="text-muted-foreground">Gestión de trámites para curadurías urbanas.</p>
        <div>
            <Button as-child>
                <router-link :to="{ name: 'platform.tenants' }">
                    Panel de plataforma
                    <ArrowRight />
                </router-link>
            </Button>
        </div>
    </div>
</template>
