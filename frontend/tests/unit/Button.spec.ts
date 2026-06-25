import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { Button } from '@/components/ui/button'

describe('Button', () => {
  it('renders with primary variant by default', () => {
    const wrapper = mount(Button, { slots: { default: 'Save' } })
    expect(wrapper.text()).toBe('Save')
    expect(wrapper.classes().join(' ')).toContain('bg-primary')
  })

  it('applies secondary variant classes', () => {
    const wrapper = mount(Button, {
      props: { variant: 'secondary' },
      slots: { default: 'Cancel' },
    })
    expect(wrapper.classes().join(' ')).toContain('bg-secondary')
  })

  it('disables interaction when disabled', () => {
    const wrapper = mount(Button, {
      props: { disabled: true },
      slots: { default: 'Submit' },
    })
    expect(wrapper.attributes('disabled')).toBeDefined()
  })
})
