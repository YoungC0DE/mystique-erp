<script setup lang="ts">
import { useToast } from '@/composables/useToast'
import { ToastItem } from '@/components/ui/toast'

const { toasts, dismiss } = useToast()
</script>

<template>
  <Teleport to="body">
    <div class="fixed bottom-5 right-5 z-[200] flex flex-col gap-2.5">
      <TransitionGroup name="fade">
        <ToastItem
          v-for="t in toasts"
          :key="t.id"
          :type="t.type"
          :message="t.message"
          @dismiss="dismiss(t.id)"
        />
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
