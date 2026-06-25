import { createPinia, setActivePinia } from 'pinia';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { createRouter, createMemoryHistory } from 'vue-router';
import LoginPage from '@/pages/LoginPage.vue';
import { i18n } from '@/i18n';

describe('LoginPage', () => {
  beforeEach(() => {
    localStorage.clear();
    setActivePinia(createPinia());
  });

  it('renders translated login copy', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/entrar', name: 'login', component: LoginPage },
        { path: '/registrar', name: 'register', component: { template: '<div />' } },
      ],
    });

    const wrapper = mount(LoginPage, {
      global: { plugins: [router, i18n] },
    });

    await router.isReady();

    expect(wrapper.text()).toContain('Mystique CRM');
    expect(wrapper.text()).toContain('Entrar');
    expect(wrapper.text()).toContain('Esqueci minha senha');
    expect(wrapper.find('#email').exists()).toBe(true);
    expect(wrapper.find('#password').exists()).toBe(true);
  });
});
