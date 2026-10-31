<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { TooltipArrow, TooltipContent, TooltipPortal, useForwardPropsEmits, type TooltipContentEmits, type TooltipContentProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<TooltipContentProps & { class?: HTMLAttributes['class'] }>(), { sideOffset: 6 });
const emits = defineEmits<TooltipContentEmits>();

const forwarded = useForwardPropsEmits({ ...props, class: undefined }, emits);
</script>

<template>
    <TooltipPortal>
        <TooltipContent
            v-bind="forwarded"
            :class="
                cn(
                    'z-50 max-w-64 rounded-md bg-foreground px-2.5 py-1.5 text-xs text-background shadow-md',
                    'data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=delayed-open]:animate-in data-[state=delayed-open]:fade-in-0',
                    props.class,
                )
            "
        >
            <slot />
            <TooltipArrow class="fill-foreground" />
        </TooltipContent>
    </TooltipPortal>
</template>
