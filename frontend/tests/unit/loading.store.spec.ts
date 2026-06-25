import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useLoadingStore } from '@/stores/loading'

describe('useLoadingStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('tracks concurrent requests', () => {
    const store = useLoadingStore()

    expect(store.isLoading).toBe(false)

    store.start()
    store.start()
    expect(store.isLoading).toBe(true)
    expect(store.pending).toBe(2)

    store.finish()
    expect(store.isLoading).toBe(true)

    store.finish()
    expect(store.isLoading).toBe(false)
  })

  it('does not go below zero on finish', () => {
    const store = useLoadingStore()
    store.finish()
    expect(store.pending).toBe(0)
  })
})
