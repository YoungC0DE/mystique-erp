import { createPinia, setActivePinia } from 'pinia';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createRouter, createMemoryHistory } from 'vue-router';
import ProfilePage from '@/pages/ProfilePage.vue';
import { i18n } from '@/i18n';
import { useAuthStore } from '@/stores/auth';

vi.mock('@/services/profile.service', () => ({
  profileService: {
    update: vi.fn(),
    updatePassword: vi.fn(),
  },
}));

vi.mock('@/composables/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
  }),
}));

describe('ProfilePage', () => {
  beforeEach(() => {
    localStorage.clear();
    setActivePinia(createPinia());

    const auth = useAuthStore();
    auth.user = {
      id: 'u-1',
      name: 'Rafael Silva',
      email: 'rafael@email.com',
      is_admin: false,
      locale: 'pt-BR',
      permissions: ['read'],
    };
  });

  it('renders personal account and password sections only', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/perfil', name: 'profile', component: ProfilePage }],
    });

    const wrapper = mount(ProfilePage, {
      global: { plugins: [router, i18n] },
    });

    await router.isReady();

    const text = wrapper.text();

    expect(text).toContain('Perfil');
    expect(text).toContain('Dados da conta');
    expect(text).toContain('Alterar senha');
    expect(text).toContain('Nome');
    expect(text).toContain('E-mail');
    expect(text).toContain('Idioma');
    expect(text).not.toMatch(/plano|empresa|plan/i);
  });
});
