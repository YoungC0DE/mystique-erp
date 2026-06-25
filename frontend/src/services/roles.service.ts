import { http } from './http';
import type { Paginated, Permission, Role } from '@/types';

export interface RolePayload {
  name: string;
  permissions?: string[];
}

export const rolesService = {
  async list(page = 1, perPage = 50): Promise<Paginated<Role>> {
    const { data } = await http.get<Paginated<Role>>('/roles', {
      params: { page, per_page: perPage },
    });
    return data;
  },

  async get(id: string): Promise<Role> {
    const { data } = await http.get<{ data: Role }>(`/roles/${id}`);
    return data.data;
  },

  async create(payload: RolePayload): Promise<Role> {
    const { data } = await http.post<{ data: Role }>('/roles', payload);
    return data.data;
  },

  async update(id: string, payload: Partial<RolePayload>): Promise<Role> {
    const { data } = await http.put<{ data: Role }>(`/roles/${id}`, payload);
    return data.data;
  },

  async remove(id: string): Promise<void> {
    await http.delete(`/roles/${id}`);
  },
};

export const permissionsService = {
  async list(): Promise<Permission[]> {
    const { data } = await http.get<{ data: Permission[] }>('/permissions');
    return data.data;
  },
};
