import { http } from './http';
import type { AppLocale, User } from '@/types';

export interface ProfilePayload {
  name?: string;
  email?: string;
  locale?: AppLocale;
}

export interface PasswordPayload {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export const profileService = {
  async update(payload: ProfilePayload): Promise<User> {
    const { data } = await http.put<{ data: User }>('/me', payload);
    return data.data;
  },

  async updatePassword(payload: PasswordPayload): Promise<void> {
    await http.put('/me/password', payload);
  },
};
