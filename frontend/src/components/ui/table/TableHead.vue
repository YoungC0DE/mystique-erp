<script setup lang="ts">
import type { HTMLAttributes, ThHTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] } & ThHTMLAttributes>();
</script>

<template>
  <th
    :class="
      cn(
        'h-11 px-4 text-left align-middle text-xs font-semibold uppercase tracking-wide text-muted-foreground bg-muted/30 first:rounded-tl-xl last:rounded-tr-xl [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]',
        props.class,
      )
    "
  >
    <slot />
  </th>
</template>
