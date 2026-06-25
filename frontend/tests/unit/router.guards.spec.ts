import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

describe('router guards', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('redirects non-admin users away from settings', async () => {
    const auth = useAuthStore()
    auth.user = {
      id: 'u-1',
      name: 'User',
      email: 'user@example.com',
      is_admin: false,
      locale: 'pt-BR',
      permissions: ['read'],
    }
    auth.initialized = true

    const { router } = await import('@/router')

    await router.push('/configuracoes')
    await router.isReady()

    expect(router.currentRoute.value.name).toBe('dashboard')
  })

  it('allows admin users to access settings', async () => {
    const auth = useAuthStore()
    auth.user = {
      id: 'u-1',
      name: 'Admin',
      email: 'admin@example.com',
      is_admin: true,
      locale: 'pt-BR',
      permissions: [],
    }
    auth.initialized = true

    const { router } = await import('@/router')

    await router.push('/configuracoes')
    await router.isReady()

    expect(router.currentRoute.value.name).toBe('settings')
  })
})
