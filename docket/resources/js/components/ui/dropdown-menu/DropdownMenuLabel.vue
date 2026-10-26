<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { DropdownMenuLabel, useForwardProps, type DropdownMenuLabelProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<DropdownMenuLabelProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps({ ...props, class: undefined });
</script>

<template>
    <DropdownMenuLabel v-bind="forwarded" :class="cn('px-2 py-1.5 text-sm font-medium', props.class)">
        <slot />
    </DropdownMenuLabel>
</template>
