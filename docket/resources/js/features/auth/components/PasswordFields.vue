<script setup lang="ts">
import { computed, ref } from 'vue';
import { Eye, EyeOff, Sparkles } from 'lucide-vue-next';
import { IconButton } from '@/components/ui/icon-button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { generatePassword, MIN_PASSWORD_LENGTH } from '@/lib/password';

/**
 * Contraseña nueva y su confirmación, con un botón para sugerir una (queda en los
 * dos campos y a la vista, para poder anotarla) y otro para mostrar u ocultar lo
 * escrito. Los ids son `<id>` y `<id>_confirmation`. La política real la valida el
 * servidor; acá solo el largo mínimo del navegador.
 */
const props = defineProps<{ id: string }>();
const password = defineModel<string>('password', { required: true });
const confirmation = defineModel<string>('confirmation', { required: true });

const visible = ref(false);
const suggested = ref(false);
const inputType = computed(() => (visible.value ? 'text' : 'password'));

function suggest(): void {
    const value = generatePassword();
    password.value = value;
    confirmation.value = value;
    visible.value = true;
    suggested.value = true;
}
</script>

<template>
    <div class="space-y-2">
        <Label :for="props.id">Contraseña</Label>
        <div class="flex items-center gap-2">
            <Input :id="props.id" v-model="password" :type="inputType" autocomplete="new-password" required :minlength="MIN_PASSWORD_LENGTH" />
            <IconButton label="Sugerir contraseña" class="size-9" @click="suggest">
                <Sparkles aria-hidden="true" />
            </IconButton>
            <IconButton :label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" class="size-9" @click="visible = !visible">
                <EyeOff v-if="visible" aria-hidden="true" />
                <Eye v-else aria-hidden="true" />
            </IconButton>
        </div>
        <p class="text-xs text-muted-foreground">Al menos {{ MIN_PASSWORD_LENGTH }} caracteres.</p>
        <p v-if="suggested" class="text-xs text-muted-foreground">Anótala o cópiala antes de guardar: después no se puede volver a ver.</p>
    </div>
    <div class="space-y-2">
        <Label :for="`${props.id}_confirmation`">Repite la contraseña</Label>
        <Input :id="`${props.id}_confirmation`" v-model="confirmation" :type="inputType" autocomplete="new-password" required />
    </div>
</template>
