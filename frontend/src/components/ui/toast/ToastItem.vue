<script setup lang="ts">
import type { ToastType } from '@/composables/useToast'
import { cn } from '@/lib/utils'

defineProps<{
  type: ToastType
  message: string
}>()

defineEmits<{ (e: 'dismiss'): void }>()

const typeClasses: Record<ToastType, string> = {
  success: 'border-l-[3px] border-l-success',
  error: 'border-l-[3px] border-l-danger',
  info: 'border-l-[3px] border-l-primary',
}
</script>

<template>
  <div
    role="status"
    :class="
      cn(
        'flex min-w-[280px] max-w-[360px] cursor-pointer items-center gap-2.5 rounded-xl border border-border/80 bg-card px-4 py-3.5 text-sm shadow-overlay backdrop-blur-sm',
        typeClasses[type],
      )
    "
    @click="$emit('dismiss')"
  >
    <span>{{ message }}</span>
  </div>
</template>
