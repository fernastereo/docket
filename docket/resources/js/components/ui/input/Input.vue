<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { useVModel } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = defineProps<{
    defaultValue?: string | number;
    modelValue?: string | number;
    class?: HTMLAttributes['class'];
}>();

const emits = defineEmits<{ (e: 'update:modelValue', payload: string | number): void }>();

const modelValue = useVModel(props, 'modelValue', emits, { passive: true, defaultValue: props.defaultValue });
</script>

<template>
    <input
        v-model="modelValue"
        :class="
            cn(
                'flex h-9 w-full min-w-0 rounded-md border border-input bg-card px-3 py-1 text-sm shadow-xs transition-colors outline-none placeholder:text-muted-foreground disabled:pointer-events-none disabled:opacity-50',
                'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/30',
                'aria-invalid:border-destructive aria-invalid:ring-destructive/20',
                props.class,
            )
        "
    />
</template>
