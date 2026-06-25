import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

type Theme = 'light' | 'dark';
const STORAGE_KEY = 'mystique.theme';

function initialTheme(): Theme {
  const saved = localStorage.getItem(STORAGE_KEY) as Theme | null;
  if (saved === 'light' || saved === 'dark') return saved;
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export const useThemeStore = defineStore('theme', () => {
  const theme = ref<Theme>(initialTheme());

  function apply(value: Theme): void {
    document.documentElement.setAttribute('data-theme', value);
  }

  function toggle(): void {
    theme.value = theme.value === 'dark' ? 'light' : 'dark';
  }

  watch(
    theme,
    (value) => {
      localStorage.setItem(STORAGE_KEY, value);
      apply(value);
    },
    { immediate: true },
  );

  return { theme, toggle };
});
