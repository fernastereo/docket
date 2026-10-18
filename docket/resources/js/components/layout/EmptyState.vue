<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{ icon: Component; title: string; description?: string }>();
</script>

<template>
    <div class="flex flex-col items-center px-6 py-14 text-center">
        <span class="flex size-11 items-center justify-center rounded-full bg-secondary text-secondary-foreground">
            <component :is="icon" class="size-5" aria-hidden="true" />
        </span>
        <h2 class="mt-4 text-base font-semibold">{{ title }}</h2>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-muted-foreground">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5">
            <slot />
        </div>
    </div>
</template>
