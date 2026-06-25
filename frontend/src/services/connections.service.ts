import { http } from './http';

import type { DatabaseConnection, DatabaseColumn } from '@/types';

export interface ConnectionPayload {
  name: string;
  host: string;
  port: number;
  database: string;
  username: string;
  password?: string;
  table_name: string;
}

export const connectionsService = {
  async list(): Promise<DatabaseConnection[]> {
    const { data } = await http.get<{ data: DatabaseConnection[] }>('/connections');
    return data.data;
  },

  async create(payload: ConnectionPayload): Promise<DatabaseConnection> {
    const { data } = await http.post<{ data: DatabaseConnection }>('/connections', payload);
    return data.data;
  },

  async update(id: string, payload: Partial<ConnectionPayload>): Promise<DatabaseConnection> {
    const { data } = await http.put<{ data: DatabaseConnection }>(`/connections/${id}`, payload);
    return data.data;
  },

  async remove(id: string): Promise<void> {
    await http.delete(`/connections/${id}`);
  },

  async test(id: string): Promise<void> {
    await http.post(`/connections/${id}/test`);
  },

  async validate(payload: ConnectionPayload): Promise<void> {
    await http.post('/connections/validate', payload);
  },

  async columns(id: string): Promise<DatabaseColumn[]> {
    const { data } = await http.get<{ data: DatabaseColumn[] }>(`/connections/${id}/columns`);
    return data.data;
  },
};
