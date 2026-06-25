import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { tokenStorage } from './tokenStorage'

declare global {
  interface Window {
    Pusher: typeof Pusher
    Echo?: Echo<'reverb'>
  }
}

let echo: Echo<'reverb'> | null = null

const apiBase = import.meta.env.VITE_API_URL ?? '/api'

export function initEcho(): Echo<'reverb'> {
  if (echo) return echo

  window.Pusher = Pusher

  echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY ?? 'mystique-key',
    wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${apiBase}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${tokenStorage.getAccess() ?? ''}`,
        Accept: 'application/json',
      },
    },
  })

  window.Echo = echo
  return echo
}

export function getEcho(): Echo<'reverb'> {
  return echo ?? initEcho()
}

export function disconnectEcho(): void {
  echo?.disconnect()
  echo = null
  window.Echo = undefined
}
