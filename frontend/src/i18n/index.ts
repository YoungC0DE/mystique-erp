import { createI18n } from 'vue-i18n';
import ptBR from '@/locales/pt-BR';
import en from '@/locales/en';
import { http } from '@/services/http';

export type AppLocale = 'pt-BR' | 'en';

export const SUPPORTED_LOCALES: AppLocale[] = ['pt-BR', 'en'];
export const DEFAULT_LOCALE: AppLocale = 'pt-BR';
const STORAGE_KEY = 'mystique.locale';

function isSupported(value: unknown): value is AppLocale {
  return typeof value === 'string' && SUPPORTED_LOCALES.includes(value as AppLocale);
}

export function storedLocale(): AppLocale {
  const saved = localStorage.getItem(STORAGE_KEY);
  return isSupported(saved) ? saved : DEFAULT_LOCALE;
}

export const i18n = createI18n({
  legacy: false,
  locale: storedLocale(),
  fallbackLocale: DEFAULT_LOCALE,
  messages: { 'pt-BR': ptBR, en },
});

/** Aplica o idioma na app, persiste e sincroniza o header HTTP. */
export function setLocale(locale: AppLocale): void {
  i18n.global.locale.value = locale;
  localStorage.setItem(STORAGE_KEY, locale);
  document.documentElement.setAttribute('lang', locale);
  http.defaults.headers.common['Accept-Language'] = locale;
}

setLocale(storedLocale());
