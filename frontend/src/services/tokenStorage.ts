import type { Tokens } from '@/types'

const ACCESS_KEY = 'mystique.access_token'
const REFRESH_KEY = 'mystique.refresh_token'

export const tokenStorage = {
  getAccess(): string | null {
    return localStorage.getItem(ACCESS_KEY)
  },
  getRefresh(): string | null {
    return localStorage.getItem(REFRESH_KEY)
  },
  set(tokens: Tokens): void {
    localStorage.setItem(ACCESS_KEY, tokens.access_token)
    if (tokens.refresh_token) {
      localStorage.setItem(REFRESH_KEY, tokens.refresh_token)
    }
  },
  clear(): void {
    localStorage.removeItem(ACCESS_KEY)
    localStorage.removeItem(REFRESH_KEY)
  },
  hasSession(): boolean {
    return !!localStorage.getItem(ACCESS_KEY)
  },
}
