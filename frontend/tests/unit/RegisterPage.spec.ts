import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import RegisterPage from '@/pages/RegisterPage.vue'
import { i18n } from '@/i18n'

describe('RegisterPage', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('renders translated register copy', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/registrar', name: 'register', component: RegisterPage },
        { path: '/entrar', name: 'login', component: { template: '<div />' } },
      ],
    })

    const wrapper = mount(RegisterPage, {
      global: { plugins: [router, i18n] },
    })

    await router.isReady()

    expect(wrapper.text()).toContain('Mystique CRM')
    expect(wrapper.text()).toContain('Criar conta')
    expect(wrapper.find('#name').exists()).toBe(true)
    expect(wrapper.find('#email').exists()).toBe(true)
    expect(wrapper.find('#password').exists()).toBe(true)
  })
})
