import { ref } from 'vue'

export type ToastType = 'success' | 'error' | 'info'

export interface Toast {
  id: number
  type: ToastType
  message: string
}

const toasts = ref<Toast[]>([])
let counter = 0

export function useToast() {
  function push(message: string, type: ToastType = 'info', timeout = 4000): void {
    const id = ++counter
    toasts.value.push({ id, type, message })
    if (timeout > 0) {
      setTimeout(() => dismiss(id), timeout)
    }
  }

  function dismiss(id: number): void {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  return {
    toasts,
    dismiss,
    success: (m: string) => push(m, 'success'),
    error: (m: string) => push(m, 'error'),
    info: (m: string) => push(m, 'info'),
  }
}
