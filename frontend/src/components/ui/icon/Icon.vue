<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import { resolveLucideIcon } from '@/lib/icon'

const props = withDefaults(
  defineProps<{
    name: string
    size?: number | string
    strokeWidth?: number | string
    class?: HTMLAttributes['class']
  }>(),
  {
    size: 20,
    strokeWidth: 2,
  },
)

const icon = computed(() => resolveLucideIcon(props.name))
</script>

<template>
  <component
    :is="icon"
    :size="typeof size === 'number' ? size : undefined"
    :stroke-width="strokeWidth"
    :class="cn('shrink-0', props.class)"
    aria-hidden="true"
  />
</template>
