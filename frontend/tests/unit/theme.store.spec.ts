import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { nextTick } from 'vue'
import { useThemeStore } from '@/stores/theme'

describe('theme store', () => {
  beforeEach(() => {
    localStorage.clear()
    document.documentElement.removeAttribute('data-theme')
    setActivePinia(createPinia())
  })

  it('defaults to light when nothing is saved and SO prefers light', () => {
    const store = useThemeStore()
    expect(store.theme).toBe('light')
  })

  it('applies the theme to the document root immediately', () => {
    useThemeStore()
    expect(document.documentElement.getAttribute('data-theme')).toBe('light')
  })

  it('toggles and persists the theme', async () => {
    const store = useThemeStore()
    store.toggle()
    await nextTick()

    expect(store.theme).toBe('dark')
    expect(localStorage.getItem('mystique.theme')).toBe('dark')
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')
  })

  it('restores a previously saved theme', () => {
    localStorage.setItem('mystique.theme', 'dark')
    setActivePinia(createPinia())

    const store = useThemeStore()
    expect(store.theme).toBe('dark')
  })
})
