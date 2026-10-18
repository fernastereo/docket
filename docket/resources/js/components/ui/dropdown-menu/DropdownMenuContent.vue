<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { DropdownMenuContent, DropdownMenuPortal, useForwardPropsEmits, type DropdownMenuContentEmits, type DropdownMenuContentProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<DropdownMenuContentProps & { class?: HTMLAttributes['class'] }>(), { sideOffset: 6 });
const emits = defineEmits<DropdownMenuContentEmits>();

const forwarded = useForwardPropsEmits({ ...props, class: undefined }, emits);
</script>

<template>
    <DropdownMenuPortal>
        <DropdownMenuContent
            v-bind="forwarded"
            :class="
                cn(
                    'z-50 min-w-48 overflow-hidden rounded-lg border bg-popover p-1 text-popover-foreground shadow-md',
                    'data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0',
                    props.class,
                )
            "
        >
            <slot />
        </DropdownMenuContent>
    </DropdownMenuPortal>
</template>
