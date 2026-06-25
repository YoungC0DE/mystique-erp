import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useToast } from '@/composables/useToast'

describe('useToast', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    const { toasts, dismiss } = useToast()
    toasts.value.slice().forEach((t) => dismiss(t.id))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('pushes a toast with the correct type', () => {
    const { toasts, success } = useToast()
    success('Salvo!')

    expect(toasts.value).toHaveLength(1)
    expect(toasts.value[0]).toMatchObject({ type: 'success', message: 'Salvo!' })
  })

  it('assigns unique incremental ids', () => {
    const { toasts, info, error } = useToast()
    info('um')
    error('dois')

    expect(toasts.value).toHaveLength(2)
    expect(toasts.value[0].id).not.toBe(toasts.value[1].id)
  })

  it('auto-dismisses after the timeout', () => {
    const { toasts, success } = useToast()
    success('some')

    expect(toasts.value).toHaveLength(1)
    vi.advanceTimersByTime(4000)
    expect(toasts.value).toHaveLength(0)
  })

  it('dismisses a toast manually by id', () => {
    const { toasts, error, dismiss } = useToast()
    error('erro')
    const id = toasts.value[0].id

    dismiss(id)
    expect(toasts.value).toHaveLength(0)
  })
})
