<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineProps<{
  title: string;
  subtitle?: string;
  class?: HTMLAttributes['class'];
}>();
</script>

<template>
  <div :class="cn('mb-7 flex flex-wrap items-start justify-between gap-4', $props.class)">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
      <p v-if="subtitle" class="mt-1.5 max-w-xl text-sm text-muted-foreground">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>
