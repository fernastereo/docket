<script setup lang="ts">
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Círculo con las iniciales de una persona. El tono sale de su nombre (siempre el
 * mismo para la misma persona) entre tres de la paleta, para distinguir filas de
 * un vistazo sin recurrir a fotos. Es decorativo: el nombre va siempre al lado.
 */
const props = defineProps<{ name: string; class?: HTMLAttributes['class'] }>();

const initials = computed(() =>
    props.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join(''),
);

const tones = [
    'bg-lav-100 text-lav-700 dark:bg-lav-700 dark:text-lav-100',
    'bg-turq-100 text-turq-800 dark:bg-turq-800 dark:text-turq-100',
    'bg-peri-100 text-peri-700 dark:bg-peri-700 dark:text-peri-100',
];
const tone = computed(() => tones[[...props.name].reduce((sum, char) => sum + char.charCodeAt(0), 0) % tones.length]);
</script>

<template>
    <span :class="cn('flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold', tone, props.class)" aria-hidden="true">
        {{ initials }}
    </span>
</template>
