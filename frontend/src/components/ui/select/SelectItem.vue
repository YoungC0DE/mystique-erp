<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { SelectItem, SelectItemIndicator, SelectItemText, type SelectItemProps } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<
  SelectItemProps & { class?: HTMLAttributes['class'] }
>()
</script>

<template>
  <SelectItem
    v-bind="props"
    :class="
      cn(
        'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
        props.class,
      )
    "
  >
    <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <SelectItemIndicator>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
          <path d="M20 6 9 17l-5-5" />
        </svg>
      </SelectItemIndicator>
    </span>
    <SelectItemText>
      <slot />
    </SelectItemText>
  </SelectItem>
</template>
