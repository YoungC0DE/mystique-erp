import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type { PermissionSlug, User } from '@/types';
import { authService } from '@/services/auth.service';
import { tokenStorage } from '@/services/tokenStorage';
import { disconnectEcho } from '@/services/echo';
import { setLocale } from '@/i18n';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const initialized = ref(false);

  const isAuthenticated = computed(() => !!user.value);
  const isAdmin = computed(() => user.value?.is_admin ?? false);
  const permissions = computed(() => user.value?.permissions ?? []);

  function can(permission: PermissionSlug | string): boolean {
    if (isAdmin.value) return true;
    return permissions.value.includes(permission);
  }

  /** Aplica os dados do usuário no estado e sincroniza o idioma. */
  function setUser(u: User | null): void {
    user.value = u;
    if (u?.locale) setLocale(u.locale);
  }

  async function login(email: string, password: string): Promise<void> {
    const { user: u, tokens } = await authService.login(email, password);
    tokenStorage.set(tokens);
    setUser(u);
  }

  async function register(name: string, email: string, password: string): Promise<void> {
    const { user: u, tokens } = await authService.register(name, email, password);
    tokenStorage.set(tokens);
    setUser(u);
  }

  async function fetchMe(): Promise<void> {
    setUser(await authService.me(true));
  }

  /** Restaura a sessão a partir do token salvo (chamado no boot do app). */
  async function hydrate(): Promise<void> {
    if (initialized.value) return;

    if (tokenStorage.hasSession()) {
      try {
        await fetchMe();
      } catch {
        tokenStorage.clear();
        user.value = null;
      }
    }

    initialized.value = true;
  }

  async function logout(): Promise<void> {
    try {
      await authService.logout();
    } catch {
      // mesmo se falhar no servidor, limpamos o cliente
    } finally {
      reset();
    }
  }

  function reset(): void {
    user.value = null;
    tokenStorage.clear();
    disconnectEcho();
  }

  /** Atualiza o usuário em memória após salvar o perfil. */
  function updateUser(u: User): void {
    setUser(u);
  }

  return {
    user,
    initialized,
    isAuthenticated,
    isAdmin,
    permissions,
    can,
    login,
    register,
    fetchMe,
    hydrate,
    logout,
    reset,
    updateUser,
  };
});
