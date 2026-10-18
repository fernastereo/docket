import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Badge } from './Badge.vue';

export const badgeVariants = cva(
    'inline-flex w-fit shrink-0 items-center justify-center gap-1 rounded-md border px-2 py-0.5 text-xs font-medium whitespace-nowrap [&>svg]:size-3',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-primary text-primary-foreground',
                secondary: 'border-transparent bg-secondary text-secondary-foreground',
                outline: 'text-foreground',
                // Estados de negocio (ver StatusBadge): tonos de la paleta, no semáforo genérico.
                success: 'border-transparent bg-success/15 text-success',
                info: 'border-transparent bg-info/15 text-info',
                warning: 'border-transparent bg-warning/20 text-warning',
                destructive: 'border-transparent bg-destructive/12 text-destructive',
            },
        },
        defaultVariants: { variant: 'default' },
    },
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;
