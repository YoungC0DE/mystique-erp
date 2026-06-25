<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card'
import { Icon } from '@/components/ui/icon'

defineProps<{
  icon?: string
  title: string
  description?: string
}>()
</script>

<template>
  <Card>
    <CardContent class="flex flex-col items-center py-14 text-center">
      <span
        v-if="icon"
        class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-muted text-muted-foreground"
      >
        <Icon :name="icon" :size="22" />
      </span>
      <p class="text-sm font-medium text-foreground">{{ title }}</p>
      <p v-if="description" class="mt-1.5 max-w-sm text-sm text-muted-foreground">{{ description }}</p>
      <div v-if="$slots.default" class="mt-4">
        <slot />
      </div>
    </CardContent>
  </Card>
</template>
