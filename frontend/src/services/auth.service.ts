import { http } from './http';
import type { Tokens, User } from '@/types';

interface LoginResponse {
  user: { data: User } | User;
  token: Tokens;
}

function unwrap<T>(payload: { data: T } | T): T {
  return (payload as { data: T }).data ?? (payload as T);
}

export const authService = {
  async login(email: string, password: string): Promise<{ user: User; tokens: Tokens }> {
    const { data } = await http.post<LoginResponse>('/auth/login', { email, password });
    return { user: unwrap(data.user), tokens: data.token };
  },

  async register(name: string, email: string, password: string): Promise<{ user: User; tokens: Tokens }> {
    const { data } = await http.post<LoginResponse>('/auth/register', { name, email, password });
    return { user: unwrap(data.user), tokens: data.token };
  },

  async me(skipGlobalLoading = false): Promise<User> {
    const { data } = await http.get<{ data: User }>('/auth/me', { skipGlobalLoading });
    return unwrap(data);
  },

  async logout(): Promise<void> {
    await http.post('/auth/logout');
  },
};
