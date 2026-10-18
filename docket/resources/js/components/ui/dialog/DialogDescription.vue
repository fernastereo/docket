<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { DialogDescription, useForwardProps, type DialogDescriptionProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<DialogDescriptionProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps({ ...props, class: undefined });
</script>

<template>
    <DialogDescription v-bind="forwarded" :class="cn('text-sm text-muted-foreground', props.class)">
        <slot />
    </DialogDescription>
</template>
