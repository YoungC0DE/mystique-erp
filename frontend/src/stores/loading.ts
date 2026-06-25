import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export const useLoadingStore = defineStore('loading', () => {
  const pending = ref(0)

  const isLoading = computed(() => pending.value > 0)

  function start(): void {
    pending.value += 1
  }

  function finish(): void {
    if (pending.value > 0) {
      pending.value -= 1
    }
  }

  return { pending, isLoading, start, finish }
})
