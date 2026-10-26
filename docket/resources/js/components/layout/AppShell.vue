<script setup lang="ts">
import { ref } from 'vue';
import { Menu } from 'lucide-vue-next';
import BrandMark from '@/components/layout/BrandMark.vue';
import SidebarContent from '@/components/layout/SidebarContent.vue';
import type { NavItem, ShellUser } from '@/components/layout/nav';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';

/**
 * Estructura común de la aplicación: navegación lateral (cajón en móvil) y
 * área de contenido. La usan el panel de plataforma y, luego, las pantallas de
 * cada curaduría: cambia el menú (`items`), no el diseño.
 */
defineProps<{ items: NavItem[]; user: ShellUser | null }>();
const emit = defineEmits<{ logout: [] }>();

const mobileOpen = ref(false);
</script>

<template>
    <div class="min-h-screen">
        <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-sidebar-border lg:block">
            <SidebarContent :items="items" :user="user" @logout="emit('logout')" />
        </aside>

        <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b bg-background/90 px-4 backdrop-blur lg:hidden">
            <Sheet v-model:open="mobileOpen">
                <SheetTrigger as-child>
                    <Button variant="ghost" size="icon" aria-label="Abrir el menú">
                        <Menu />
                    </Button>
                </SheetTrigger>
                <SheetContent>
                    <SheetTitle>Menú</SheetTitle>
                    <SidebarContent :items="items" :user="user" @logout="emit('logout')" @navigate="mobileOpen = false" />
                </SheetContent>
            </Sheet>
            <BrandMark />
        </header>

        <main class="lg:pl-64">
            <div class="mx-auto max-w-6xl px-4 py-8 sm:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>
