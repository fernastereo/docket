<script setup lang="ts">
import BrandMark from '@/components/layout/BrandMark.vue';

defineProps<{
    /** Nota al pie del panel de marca (solo pantallas grandes). */
    asideNote: string;
}>();
</script>

<template>
    <div class="grid min-h-screen lg:grid-cols-2">
        <aside class="surface-brand hidden flex-col justify-between p-12 text-lav-50 lg:flex">
            <BrandMark />
            <div class="max-w-md">
                <p class="text-3xl leading-tight font-semibold tracking-tight">
                    Expedientes, plazos y actos administrativos de cada curaduría, en un solo lugar.
                </p>
                <p class="mt-4 text-sm text-lav-300">{{ asideNote }}</p>
            </div>
        </aside>

        <main class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-sm">
                <div class="mb-10 lg:hidden">
                    <BrandMark />
                </div>
                <slot />
            </div>
        </main>
    </div>
</template>
