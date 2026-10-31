<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

/**
 * Acción solo con icono (el icono va en el slot) y un tooltip que dice qué hace.
 * Es el estilo de TODA acción clicable que sea solo un icono: sin borde ni fondo,
 * mano al pasar el ratón y cambio de color a turquesa (docs/diseno-ui.md §4).
 * `label` es el texto del tooltip; el lector de pantalla lee `ariaLabel` (por
 * defecto, el mismo `label`), que puede ser más concreto ("… a ana@cu.test").
 *
 * Deshabilitado, un botón no recibe el puntero ni el foco: el tooltip se pone
 * en un `span` enfocable alrededor para poder explicar por qué (`disabledReason`).
 * Activo, el disparador es el propio botón, porque Reka abre el tooltip con el
 * evento `focus` (que no sube por el DOM).
 */
defineOptions({ inheritAttrs: false });

const props = defineProps<{
    label: string;
    ariaLabel?: string;
    disabled?: boolean;
    disabledReason?: string;
    class?: HTMLAttributes['class'];
}>();
const emit = defineEmits<{ click: [] }>();

</script>

<template>
    <TooltipProvider :delay-duration="150">
        <Tooltip>
            <TooltipTrigger as-child>
                <span v-if="disabled" tabindex="0" class="inline-flex cursor-not-allowed rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40">
                    <Button type="button" variant="action" size="icon" :class="['size-8', props.class]" disabled :aria-label="ariaLabel ?? label">
                        <slot />
                    </Button>
                </span>
                <Button v-else type="button" variant="action" size="icon" :class="['size-8', props.class]" :aria-label="ariaLabel ?? label" @click="emit('click')">
                    <slot />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ disabled && disabledReason ? disabledReason : label }}</TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
