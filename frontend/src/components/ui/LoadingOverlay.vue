<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { Spinner } from '@/components/ui/spinner'
import { useLoadingStore } from '@/stores/loading'

const { t } = useI18n()
const loading = useLoadingStore()
const { isLoading } = storeToRefs(loading)
</script>

<template>
  <Teleport to="body">
    <Transition name="loading-overlay">
      <div
        v-if="isLoading"
        class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40"
        role="status"
        :aria-label="t('common.loading')"
        aria-live="polite"
        aria-busy="true"
      >
        <Spinner size="lg" class="h-11 w-11 border-[3px] shadow-sm" />
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.loading-overlay-enter-active,
.loading-overlay-leave-active {
  transition: opacity 0.15s ease;
}

.loading-overlay-enter-from,
.loading-overlay-leave-to {
  opacity: 0;
}
</style>
