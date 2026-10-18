<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { DropdownMenuItem, useForwardPropsEmits, type DropdownMenuItemEmits, type DropdownMenuItemProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<DropdownMenuItemProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<DropdownMenuItemEmits>();

const forwarded = useForwardPropsEmits({ ...props, class: undefined }, emits);
</script>

<template>
    <DropdownMenuItem
        v-bind="forwarded"
        :class="
            cn(
                'relative flex cursor-default items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-none select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 data-[highlighted]:bg-secondary data-[highlighted]:text-secondary-foreground [&_svg]:size-4 [&_svg]:shrink-0 [&_svg]:text-muted-foreground',
                props.class,
            )
        "
    >
        <slot />
    </DropdownMenuItem>
</template>
