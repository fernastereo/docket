<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { DropdownMenuSeparator, useForwardProps, type DropdownMenuSeparatorProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<DropdownMenuSeparatorProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps({ ...props, class: undefined });
</script>

<template>
    <DropdownMenuSeparator v-bind="forwarded" :class="cn('-mx-1 my-1 h-px bg-border', props.class)" />
</template>
