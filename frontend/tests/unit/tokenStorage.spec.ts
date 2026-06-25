import { beforeEach, describe, expect, it } from 'vitest';
import type { Tokens } from '@/types';
import { tokenStorage } from '@/services/tokenStorage';

describe('tokenStorage', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('stores access and refresh tokens', () => {
    tokenStorage.set({ access_token: 'a', refresh_token: 'r', token_type: 'Bearer', expires_in: 3600 });

    expect(tokenStorage.getAccess()).toBe('a');
    expect(tokenStorage.getRefresh()).toBe('r');
    expect(tokenStorage.hasSession()).toBe(true);
  });

  it('keeps the previous refresh token when a new one is not provided', () => {
    tokenStorage.set({ access_token: 'a1', refresh_token: 'r1', token_type: 'Bearer', expires_in: 3600 });
    tokenStorage.set({ access_token: 'a2', token_type: 'Bearer', expires_in: 3600 } as Tokens);

    expect(tokenStorage.getAccess()).toBe('a2');
    expect(tokenStorage.getRefresh()).toBe('r1');
  });

  it('clears the session', () => {
    tokenStorage.set({ access_token: 'a', refresh_token: 'r', token_type: 'Bearer', expires_in: 3600 });
    tokenStorage.clear();

    expect(tokenStorage.getAccess()).toBeNull();
    expect(tokenStorage.getRefresh()).toBeNull();
    expect(tokenStorage.hasSession()).toBe(false);
  });
});
