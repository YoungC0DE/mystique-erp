import { createPinia, setActivePinia } from 'pinia';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createRouter, createMemoryHistory } from 'vue-router';
import AppLayout from '@/components/layout/AppLayout.vue';
import { i18n } from '@/i18n';
import { useAuthStore } from '@/stores/auth';

vi.mock('@/services/echo', () => ({
  initEcho: vi.fn(),
}));

vi.mock('@/stores/modules', () => ({
  useModulesStore: () => ({
    loaded: true,
    allowed: [],
    loadAllowed: vi.fn().mockResolvedValue(undefined),
    reset: vi.fn(),
  }),
}));

const stubRoute = { template: '<div />' };

function layoutRoutes(extra: { path: string; name: string }[] = []) {
  return [
    { path: '/', name: 'dashboard', component: stubRoute },
    { path: '/modules', name: 'modules', component: stubRoute },
    { path: '/users', name: 'users', component: stubRoute },
    { path: '/roles', name: 'roles', component: stubRoute },
    { path: '/reports', name: 'reports', component: stubRoute },
    { path: '/perfil', name: 'profile', component: stubRoute },
    { path: '/configuracoes', name: 'settings', component: stubRoute },
    ...extra.map((r) => ({ ...r, component: stubRoute })),
  ];
}

describe('AppLayout', () => {
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

  it('renders brand and user initials in the topbar', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: layoutRoutes(),
    });

    const wrapper = mount(AppLayout, {
      global: {
        plugins: [router, i18n],
        stubs: { RouterView: true },
      },
    });

    await router.isReady();

    expect(wrapper.text()).toContain('Mystique');
    expect(wrapper.text()).toContain('RS');
    expect(wrapper.text()).toContain('Rafael Silva');
    expect(wrapper.text()).not.toContain('Sair');
  });

  it('shows user menu items when the chip is clicked', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: layoutRoutes(),
    });

    const wrapper = mount(AppLayout, {
      global: {
        plugins: [router, i18n],
        stubs: { RouterView: true },
      },
      attachTo: document.body,
    });

    await router.isReady();

    await wrapper.get('[aria-label="Abrir menu do usuário"]').trigger('click');

    expect(document.body.textContent).toContain('Perfil');
    expect(document.body.textContent).toContain('Sair');
    expect(document.body.textContent).not.toContain('Configurações');

    wrapper.unmount();
  });

  it('shows settings in the menu for admins', async () => {
    const auth = useAuthStore();
    auth.user = {
      id: 'u-1',
      name: 'Admin User',
      email: 'admin@email.com',
      is_admin: true,
      locale: 'pt-BR',
      permissions: [],
    };

    const router = createRouter({
      history: createMemoryHistory(),
      routes: layoutRoutes(),
    });

    const wrapper = mount(AppLayout, {
      global: {
        plugins: [router, i18n],
        stubs: { RouterView: true },
      },
      attachTo: document.body,
    });

    await router.isReady();

    await wrapper.get('[aria-label="Abrir menu do usuário"]').trigger('click');

    expect(document.body.textContent).toContain('Configurações');

    wrapper.unmount();
  });
});
