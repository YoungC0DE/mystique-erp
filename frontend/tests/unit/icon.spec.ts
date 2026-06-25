import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { Kanban, ShoppingCart } from '@lucide/vue'
import { Icon } from '@/components/ui/icon'

describe('Icon', () => {
  it('renders a Lucide icon by kebab-case name', () => {
    const wrapper = mount(Icon, {
      props: { name: 'shopping-cart', size: 24 },
    })

    expect(wrapper.findComponent(ShoppingCart).exists()).toBe(true)
  })

  it('falls back to Kanban when name is empty', () => {
    const wrapper = mount(Icon, {
      props: { name: '' },
    })

    expect(wrapper.findComponent(Kanban).exists()).toBe(true)
  })

  it('falls back to Kanban for unknown icon names', () => {
    const wrapper = mount(Icon, {
      props: { name: 'not-a-real-icon' },
    })

    expect(wrapper.findComponent(Kanban).exists()).toBe(true)
  })
})
