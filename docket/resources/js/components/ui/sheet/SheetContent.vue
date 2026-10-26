<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { DialogContent, DialogDescription, DialogOverlay, DialogPortal, useForwardPropsEmits, type DialogContentEmits, type DialogContentProps } from 'reka-ui';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<DialogContentProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<DialogContentEmits>();

const forwarded = useForwardPropsEmits({ ...props, class: undefined }, emits);
</script>

<template>
    <DialogPortal>
        <DialogOverlay
            class="fixed inset-0 z-50 bg-indigo-x-950/60 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
        />
        <DialogContent
            v-bind="{ ...$attrs, ...forwarded }"
            :class="
                cn(
                    'fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col outline-none',
                    'data-[state=closed]:animate-out data-[state=closed]:slide-out-to-left data-[state=open]:animate-in data-[state=open]:slide-in-from-left',
                    props.class,
                )
            "
        >
            <DialogDescription class="sr-only">Navegación principal</DialogDescription>
            <slot />
        </DialogContent>
    </DialogPortal>
</template>
