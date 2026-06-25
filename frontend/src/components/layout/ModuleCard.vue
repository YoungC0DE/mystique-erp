<script setup lang="ts">
import { Icon } from '@/components/ui/icon'
import { cn } from '@/lib/utils'

defineProps<{
  name: string
  icon?: string
  description?: string
  to?: object | string
}>()
</script>

<template>
  <RouterLink
    v-if="to"
    :to="to"
    :class="
      cn(
        'group flex flex-col gap-2 rounded-xl border border-border/80 bg-card p-5 shadow-card',
        'transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/35 hover:shadow-card-hover',
      )
    "
  >
    <span
      class="grid h-11 w-11 place-items-center rounded-xl bg-primary/10 text-primary transition-colors duration-200 group-hover:bg-primary/15"
    >
      <Icon :name="icon ?? 'layout-grid'" :size="22" />
    </span>
    <strong class="text-[15px] font-semibold tracking-tight">{{ name }}</strong>
    <span v-if="description" class="text-sm text-muted-foreground">{{ description }}</span>
  </RouterLink>
</template>
