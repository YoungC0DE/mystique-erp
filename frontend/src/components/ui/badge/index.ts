import { cva, type VariantProps } from 'class-variance-authority';

export { default as Badge } from './Badge.vue';

export const badgeVariants = cva(
  'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors',
  {
    variants: {
      variant: {
        default: 'border-transparent bg-primary text-primary-foreground',
        secondary: 'border-transparent bg-secondary text-secondary-foreground',
        muted: 'border-transparent bg-muted text-muted-foreground',
        primary: 'border-primary/15 bg-primary/10 text-primary',
        outline: 'border-border bg-card text-foreground',
        success: 'border-success/20 bg-success/10 text-success',
        destructive: 'border-transparent bg-destructive/10 text-destructive',
      },
    },
    defaultVariants: {
      variant: 'secondary',
    },
  },
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;
