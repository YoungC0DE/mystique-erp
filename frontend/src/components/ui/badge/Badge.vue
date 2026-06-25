<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Primitive, type PrimitiveProps } from 'reka-ui';
import { cn } from '@/lib/utils';
import { type BadgeVariants, badgeVariants } from '.';

const props = defineProps<
  PrimitiveProps & {
    variant?: BadgeVariants['variant'];
    class?: HTMLAttributes['class'];
  }
>();
</script>

<template>
  <Primitive :as="as" :as-child="asChild" :class="cn(badgeVariants({ variant }), props.class)">
    <slot />
  </Primitive>
</template>
