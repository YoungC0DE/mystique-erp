import { describe, expect, it } from 'vitest'
import { cn } from '@/lib/utils'

describe('cn', () => {
  it('combines class names', () => {
    expect(cn('foo', 'bar')).toBe('foo bar')
  })

  it('merges conflicting tailwind utilities', () => {
    expect(cn('px-2 py-1', 'px-4')).toBe('py-1 px-4')
  })

  it('ignores falsy values', () => {
    expect(cn('base', false && 'hidden', undefined, 'end')).toBe('base end')
  })
})
