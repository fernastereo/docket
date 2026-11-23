<script setup lang="ts">
import BrandMark from '@/components/layout/BrandMark.vue';

withDefaults(
    defineProps<{
        /** Lo que se destaca en el panel de marca: la curaduría a la que se entra (o "Panel de plataforma"). */
        asideTitle: string;
        asideNote?: string;
    }>(),
    { asideNote: 'Expedientes, plazos y actos administrativos en un solo lugar.' },
);
</script>

<template>
    <div class="grid min-h-screen lg:grid-cols-2">
        <aside class="surface-brand relative hidden flex-col justify-between overflow-hidden p-12 text-lav-50 lg:flex">
            <!-- Plano de manzanas y predios, de fondo: es una curaduría urbana, no una oficina cualquiera. -->
            <svg class="pointer-events-none absolute inset-0 size-full text-turq-300" viewBox="0 0 800 900" preserveAspectRatio="xMidYMid slice" fill="none" aria-hidden="true">
                <defs>
                    <pattern id="plan-grid" width="48" height="48" patternUnits="userSpaceOnUse">
                        <path d="M48 0H0V48" stroke="currentColor" stroke-opacity="0.07" />
                    </pattern>
                </defs>
                <rect width="800" height="900" fill="url(#plan-grid)" />
                <g stroke="currentColor" stroke-opacity="0.14" stroke-dasharray="6 8">
                    <path d="M0 330H800" />
                    <path d="M520 0V900" />
                </g>
                <g stroke="currentColor" stroke-opacity="0.24" stroke-width="1.25">
                    <path d="M96 150h336v144H96z" />
                    <path d="M180 150v144M264 150v144M348 150v144" />
                    <path d="M96 372h336v150H96z" />
                    <path d="M96 447h336M216 372v75M216 447v75M336 372v75M336 447v75" />
                    <path d="M568 150h168v144H568z" />
                    <path d="M568 372h168l-24 150H568z" />
                    <path d="M652 372v150" />
                </g>
                <!-- El predio destacado: el de la curaduría. -->
                <path d="M264 150h84v144h-84z" fill="currentColor" fill-opacity="0.12" stroke="currentColor" stroke-opacity="0.65" stroke-width="1.5" />
                <g stroke="currentColor" stroke-opacity="0.3">
                    <path d="M96 128v10M432 128v10M96 133h336" />
                    <path d="M758 150h-10M758 294h-10M753 150v144" />
                </g>
            </svg>

            <BrandMark class="relative" />
            <div class="relative max-w-md">
                <p class="text-4xl leading-tight font-semibold tracking-tight">{{ asideTitle }}</p>
                <p class="mt-3 text-base text-lav-300">{{ asideNote }}</p>
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
