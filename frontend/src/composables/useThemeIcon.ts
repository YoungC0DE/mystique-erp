import { computed, type ComputedRef } from 'vue'
import { useThemeStore } from '@/stores/theme'

/** Lucide icon name for the theme toggle (sun in dark mode, moon in light mode). */
export function useThemeIcon(): ComputedRef<string> {
  const theme = useThemeStore()
  return computed(() => (theme.theme === 'dark' ? 'sun' : 'moon'))
}
