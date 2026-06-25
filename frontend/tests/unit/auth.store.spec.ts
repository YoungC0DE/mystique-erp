import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { User } from '@/types';

const { authServiceMock, disconnectEcho } = vi.hoisted(() => ({
  authServiceMock: {
    login: vi.fn(),
    me: vi.fn(),
    logout: vi.fn(),
  },
  disconnectEcho: vi.fn(),
}));

vi.mock('@/services/auth.service', () => ({ authService: authServiceMock }));
vi.mock('@/services/echo', () => ({ disconnectEcho }));
vi.mock('@/i18n', () => ({ setLocale: vi.fn() }));

import { useAuthStore } from '@/stores/auth';
import { tokenStorage } from '@/services/tokenStorage';

function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 'u-1',
    name: 'Rafael',
    email: 'rafael@email.com',
    is_admin: false,
    locale: 'pt-BR',
    permissions: ['read'],
    ...overrides,
  };
}

const tokens = { access_token: 'a', refresh_token: 'r', token_type: 'Bearer', expires_in: 3600 };

describe('auth store', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.clearAllMocks();
    setActivePinia(createPinia());
  });

  it('logs in, stores tokens and the user', async () => {
    authServiceMock.login.mockResolvedValue({ user: makeUser(), tokens });
    const store = useAuthStore();

    await store.login('rafael@email.com', 'senha123');

    expect(authServiceMock.login).toHaveBeenCalledWith('rafael@email.com', 'senha123');
    expect(store.isAuthenticated).toBe(true);
    expect(store.user?.email).toBe('rafael@email.com');
    expect(tokenStorage.getAccess()).toBe('a');
  });

  it('rejects login when credentials are invalid', async () => {
    authServiceMock.login.mockRejectedValue(new Error('credenciais inválidas'));
    const store = useAuthStore();

    await expect(store.login('x', 'y')).rejects.toThrow();
    expect(store.isAuthenticated).toBe(false);
  });

  it('grants any permission to an admin', () => {
    const store = useAuthStore();
    store.user = makeUser({ is_admin: true, permissions: [] });

    expect(store.can('delete')).toBe(true);
    expect(store.isAdmin).toBe(true);
  });

  it('checks granular permissions for regular users', () => {
    const store = useAuthStore();
    store.user = makeUser({ permissions: ['read', 'update'] });

    expect(store.can('read')).toBe(true);
    expect(store.can('delete')).toBe(false);
  });

  it('logs out clearing user, tokens and echo', async () => {
    authServiceMock.logout.mockResolvedValue(undefined);
    tokenStorage.set(tokens);
    const store = useAuthStore();
    store.user = makeUser();

    await store.logout();

    expect(store.user).toBeNull();
    expect(tokenStorage.hasSession()).toBe(false);
    expect(disconnectEcho).toHaveBeenCalled();
  });

  it('hydrate fetches the user when a session exists', async () => {
    tokenStorage.set(tokens);
    authServiceMock.me.mockResolvedValue(makeUser());
    const store = useAuthStore();

    await store.hydrate();

    expect(authServiceMock.me).toHaveBeenCalled();
    expect(store.isAuthenticated).toBe(true);
    expect(store.initialized).toBe(true);
  });

  it('hydrate clears the session when fetching the user fails', async () => {
    tokenStorage.set(tokens);
    authServiceMock.me.mockRejectedValue(new Error('401'));
    const store = useAuthStore();

    await store.hydrate();

    expect(store.user).toBeNull();
    expect(tokenStorage.hasSession()).toBe(false);
    expect(store.initialized).toBe(true);
  });

  it('hydrate is a no-op without a stored session', async () => {
    const store = useAuthStore();

    await store.hydrate();

    expect(authServiceMock.me).not.toHaveBeenCalled();
    expect(store.initialized).toBe(true);
  });
});
