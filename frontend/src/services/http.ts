import axios, { AxiosError, type AxiosInstance, type AxiosRequestConfig, type InternalAxiosRequestConfig } from 'axios';
import type { Tokens } from '@/types';
import { useLoadingStore } from '@/stores/loading';
import { tokenStorage } from './tokenStorage';

declare module 'axios' {
  export interface AxiosRequestConfig {
    /** Não exibe o overlay global de carregamento (ex.: refresh silencioso do Kanban). */
    skipGlobalLoading?: boolean;
  }
}

const baseURL = import.meta.env.VITE_API_URL ?? '/api';

export const http: AxiosInstance = axios.create({
  baseURL,
  headers: { Accept: 'application/json' },
});

function trackLoading(config: AxiosRequestConfig | undefined, active: boolean): void {
  if (config?.skipGlobalLoading) return;
  const store = useLoadingStore();
  if (active) {
    store.start();
  } else {
    store.finish();
  }
}

// Callback acionado quando a sessão expira de vez (refresh falhou).
let onUnauthorized: (() => void) | null = null;
export function setUnauthorizedHandler(handler: () => void): void {
  onUnauthorized = handler;
}

http.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = tokenStorage.getAccess();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  trackLoading(config, true);
  return config;
});

// Controla o refresh concorrente: enquanto um refresh acontece,
// as demais requisições 401 aguardam o mesmo resultado.
let refreshing: Promise<string | null> | null = null;

async function refreshTokens(): Promise<string | null> {
  const refresh_token = tokenStorage.getRefresh();
  if (!refresh_token) return null;

  try {
    const { data } = await axios.post<{ token: Tokens }>(
      `${baseURL}/auth/refresh`,
      { refresh_token },
      { headers: { Accept: 'application/json' } },
    );
    tokenStorage.set(data.token);
    return data.token.access_token;
  } catch {
    return null;
  }
}

http.interceptors.response.use(
  (response) => {
    trackLoading(response.config, false);
    return response;
  },
  async (error: AxiosError) => {
    trackLoading(error.config, false);

    const original = error.config as (AxiosRequestConfig & { _retry?: boolean }) | undefined;
    const status = error.response?.status;
    const isAuthCall =
      original?.url?.includes('/auth/login') ||
      original?.url?.includes('/auth/register') ||
      original?.url?.includes('/auth/refresh');

    if (status === 401 && original && !original._retry && !isAuthCall) {
      original._retry = true;

      refreshing ??= refreshTokens().finally(() => {
        refreshing = null;
      });
      const newToken = await refreshing;

      if (newToken) {
        original.headers = original.headers ?? {};
        (original.headers as Record<string, string>).Authorization = `Bearer ${newToken}`;
        return http(original);
      }

      tokenStorage.clear();
      onUnauthorized?.();
    }

    return Promise.reject(error);
  },
);

/** Extrai mensagem amigável de um erro da API (sem stack trace). */
export function apiErrorMessage(error: unknown, fallback = 'Ocorreu um erro inesperado.'): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
    if (data?.errors) {
      const first = Object.values(data.errors)[0];
      if (first?.length) return first[0];
    }
    if (data?.message) return data.message;
  }
  return fallback;
}
