import { createPinia, setActivePinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import ModuleBoardPage from '@/pages/modules/ModuleBoardPage.vue'
import { i18n } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import type { KanbanBoard, Module } from '@/types'

vi.mock('@/services/echo', () => ({
  getEcho: () => ({
    private: () => ({
      listen: vi.fn(),
    }),
    leave: vi.fn(),
  }),
}))

vi.mock('@/composables/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
  }),
}))

const integratedModule: Module = {
  id: 'mod-1',
  name: 'Pedidos',
  slug: 'pedidos',
  icon: 'shopping-cart',
  status: 'active',
  is_integrated: true,
  statuses: [
    { id: 's1', slug: 'inputar', label: 'Inputar', order: 0, external_value: 'Inputar' },
    { id: 's2', slug: 'em_andamento', label: 'Em Andamento', order: 1, external_value: 'Em Andamento' },
  ],
  fields: [
    {
      id: 'f1',
      label: 'Cliente',
      key: 'cliente',
      type: 'texto',
      required: false,
      order: 0,
      show_in_card: true,
      show_in_list: true,
      show_in_detail: true,
      highlighted: false,
      visible: true,
    },
  ],
}

const board: KanbanBoard = {
  columns: [
    {
      key: 'inputar',
      label: 'Inputar',
      color: '#94a3b8',
      records: [
        {
          id: '1',
          status: 'inputar',
          values: { cliente: 'Cliente A' },
          created_at: '2026-01-01',
        },
      ],
      meta: { total: 1, per_page: 10, current_page: 1, last_page: 1 },
    },
    {
      key: 'em_andamento',
      label: 'Em Andamento',
      color: '#3b82f6',
      records: [],
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1 },
    },
  ],
}

vi.mock('@/stores/modules', () => ({
  useModulesStore: () => ({
    findBySlug: vi.fn(() => ({ id: 'mod-1', slug: 'pedidos', name: 'Pedidos' })),
    loadAllowed: vi.fn().mockResolvedValue(undefined),
  }),
}))

vi.mock('@/services/modules.service', () => ({
  modulesService: {
    get: vi.fn(() => Promise.resolve(integratedModule)),
  },
}))

vi.mock('@/services/records.service', () => ({
  recordsService: {
    kanban: vi.fn(() => Promise.resolve(board)),
    moveIntegrated: vi.fn(),
    move: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
}))

describe('ModuleBoardPage', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())

    const auth = useAuthStore()
    auth.user = {
      id: 'u-1',
      name: 'Rafael Silva',
      email: 'rafael@email.com',
      is_admin: false,
      locale: 'pt-BR',
      permissions: ['read', 'create', 'update'],
    }
  })

  it('hides new record action and shows read-only hint for integrated modules', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/m/:slug', name: 'module', component: ModuleBoardPage, props: true },
        { path: '/dashboard', name: 'dashboard', component: { template: '<div />' } },
      ],
    })

    const wrapper = mount(ModuleBoardPage, {
      props: { slug: 'pedidos' },
      global: { plugins: [router, i18n] },
    })

    await router.isReady()
    await flushPromises()

    const text = wrapper.text()

    expect(text).toContain('Pedidos')
    expect(text).toContain('somente leitura')
    expect(text).not.toContain('Novo registro')
  })
})
