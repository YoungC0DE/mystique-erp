<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card'
import { Icon } from '@/components/ui/icon'
import { cn } from '@/lib/utils'

defineProps<{
  label: string
  value: string | number
  icon?: string
  description?: string
  class?: string
}>()
</script>

<template>
  <Card
    :class="
      cn(
        'group overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:shadow-card-hover',
        $props.class,
      )
    "
  >
    <CardContent class="p-5">
      <div class="flex items-start justify-between gap-4">
        <div class="flex min-w-0 flex-col gap-1">
          <span class="text-[13px] font-medium text-muted-foreground">{{ label }}</span>
          <strong class="text-[28px] font-semibold leading-none tracking-tight">{{ value }}</strong>
          <span v-if="description" class="mt-0.5 text-xs text-muted-foreground">{{ description }}</span>
        </div>
        <span
          v-if="icon"
          class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary transition-colors duration-200 group-hover:bg-primary/15"
        >
          <Icon :name="icon" :size="20" />
        </span>
      </div>
    </CardContent>
  </Card>
</template>
