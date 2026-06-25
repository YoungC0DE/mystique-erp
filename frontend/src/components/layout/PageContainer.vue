<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
  <div :class="cn('mx-auto max-w-[1280px] animate-page-in p-6 px-7 lg:px-8', $props.class)">
    <slot />
  </div>
</template>
