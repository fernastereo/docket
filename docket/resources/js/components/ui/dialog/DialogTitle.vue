<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { DialogTitle, useForwardProps, type DialogTitleProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<DialogTitleProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps({ ...props, class: undefined });
</script>

<template>
    <DialogTitle v-bind="forwarded" :class="cn('text-lg leading-none font-semibold', props.class)">
        <slot />
    </DialogTitle>
</template>
