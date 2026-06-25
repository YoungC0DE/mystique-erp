<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { SelectIcon, SelectTrigger, type SelectTriggerProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<SelectTriggerProps & { class?: HTMLAttributes['class'] }>();
</script>

<template>
  <SelectTrigger
    v-bind="props"
    :class="
      cn(
        'flex h-10 w-full items-center justify-between rounded-lg border border-input bg-card px-3.5 py-2 text-sm shadow-sm transition-[border-color,box-shadow] hover:border-primary/30 focus:outline-none focus:ring-[3px] focus:ring-primary/15 disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1',
        props.class,
      )
    "
  >
    <slot />
    <SelectIcon as-child>
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        class="h-4 w-4 opacity-50"
      >
        <path d="m6 9 6 6 6-6" />
      </svg>
    </SelectIcon>
  </SelectTrigger>
</template>
