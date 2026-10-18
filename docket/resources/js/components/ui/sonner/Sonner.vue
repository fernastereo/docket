<script setup lang="ts">
import { Toaster as VueSonner, type ToasterProps } from 'vue-sonner';
import 'vue-sonner/style.css';

const props = defineProps<ToasterProps>();
</script>

<template>
    <VueSonner
        v-bind="props"
        class="toaster group"
        :style="{
            '--normal-bg': 'var(--popover)',
            '--normal-text': 'var(--popover-foreground)',
            '--normal-border': 'var(--border)',
        }"
    />
</template>
