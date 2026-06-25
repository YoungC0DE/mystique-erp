<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Icon } from '@/components/ui/icon'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

defineProps<{ title?: string; large?: boolean }>()
const emit = defineEmits<{ (e: 'close'): void }>()

function onOpenChange(open: boolean): void {
  if (!open) emit('close')
}
</script>

<template>
  <Dialog :open="true" @update:open="onOpenChange">
    <DialogContent
      :class="large ? 'max-w-[720px]' : 'max-w-[520px]'"
      @pointer-down-outside="emit('close')"
      @escape-key-down="emit('close')"
    >
      <DialogHeader class="shrink-0 flex-row items-center justify-between space-y-0">
        <DialogTitle>{{ title }}</DialogTitle>
        <Button variant="ghost" size="icon" type="button" aria-label="Fechar" @click="emit('close')">
          <Icon name="x" :size="20" />
        </Button>
      </DialogHeader>
      <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
        <slot />
      </div>
      <DialogFooter v-if="$slots.footer" class="shrink-0 gap-2 border-t border-border/60 bg-muted/20 px-6 py-4">
        <slot name="footer" />
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
