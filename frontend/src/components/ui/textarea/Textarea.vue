<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { controlClass } from '@/lib/inputStyles'

const model = defineModel<string>()

defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <textarea
    v-model="model"
    :class="
      cn(
        controlClass,
        'min-h-[80px] resize-y py-2',
        $props.class,
      )
    "
  />
</template>
