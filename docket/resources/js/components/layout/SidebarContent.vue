<script setup lang="ts">
import { computed } from 'vue';
import { ChevronsUpDown, LogOut, Monitor, Moon, Sun } from 'lucide-vue-next';
import BrandMark from '@/components/layout/BrandMark.vue';
import type { NavItem, ShellUser } from '@/components/layout/nav';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTheme, type Theme } from '@/composables/useTheme';

const props = defineProps<{ items: NavItem[]; user: ShellUser | null }>();
const emit = defineEmits<{ logout: []; navigate: [] }>();

const { theme, setTheme } = useTheme();

const themes: { value: Theme; label: string; icon: typeof Sun }[] = [
    { value: 'light', label: 'Claro', icon: Sun },
    { value: 'dark', label: 'Oscuro', icon: Moon },
    { value: 'system', label: 'Según el sistema', icon: Monitor },
];

const initials = computed(() =>
    (props.user?.name ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join(''),
);
</script>

<template>
    <div class="flex h-full flex-col bg-sidebar text-sidebar-foreground">
        <div class="px-5 pt-5 pb-6">
            <BrandMark />
        </div>

        <nav aria-label="Principal" class="flex-1 space-y-0.5 px-3">
            <router-link
                v-for="item in items"
                :key="item.label"
                :to="item.to"
                class="group relative flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-sidebar-muted transition-colors outline-none hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:ring-[3px] focus-visible:ring-ring/60"
                active-class="bg-sidebar-accent! text-sidebar-foreground!"
                @click="emit('navigate')"
            >
                <span
                    class="absolute top-1.5 bottom-1.5 left-0 w-0.5 rounded-full bg-sidebar-indicator opacity-0 group-[.router-link-active]:opacity-100"
                    aria-hidden="true"
                />
                <component :is="item.icon" class="size-4 shrink-0" aria-hidden="true" />
                {{ item.label }}
            </router-link>
        </nav>

        <div v-if="user" class="border-t border-sidebar-border p-3">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left outline-none transition-colors hover:bg-sidebar-accent focus-visible:ring-[3px] focus-visible:ring-ring/60"
                    >
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-sidebar-accent text-xs font-semibold"
                            aria-hidden="true"
                        >
                            {{ initials }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{ user.name }}</span>
                            <span class="block truncate text-xs text-sidebar-muted">{{ user.email }}</span>
                        </span>
                        <ChevronsUpDown class="size-4 shrink-0 text-sidebar-muted" aria-hidden="true" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" side="top" class="w-60">
                    <DropdownMenuLabel>Apariencia</DropdownMenuLabel>
                    <DropdownMenuItem v-for="option in themes" :key="option.value" @select="setTheme(option.value)">
                        <component :is="option.icon" />
                        {{ option.label }}
                        <span v-if="theme === option.value" class="ml-auto text-xs text-muted-foreground">Activo</span>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @select="emit('logout')">
                        <LogOut />
                        Cerrar sesión
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</template>
