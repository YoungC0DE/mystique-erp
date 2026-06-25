<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{ class?: HTMLAttributes['class']; size?: 'default' | 'lg' }>(),
  { size: 'default' },
)
</script>

<template>
  <div
    role="status"
    aria-label="Loading"
    :class="
      cn(
        'animate-spin rounded-full border-2 border-primary-soft border-t-primary',
        size === 'lg' ? 'h-[34px] w-[34px] border-[3px]' : 'h-[18px] w-[18px]',
        props.class,
      )
    "
  />
</template>
