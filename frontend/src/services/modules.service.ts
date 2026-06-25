import { http } from './http';
import type { Module, ModuleField, Paginated } from '@/types';

export interface ModuleColumnPayload {
  name: string;
  label?: string;
  type?: string;
}

export interface ModuleStatusPayload {
  slug: string;
  label: string;
  order: number;
  external_value: string;
}

export interface ModulePayload {
  name: string;
  slug?: string;
  icon?: string | null;
  status?: string;
  connection_id?: string;
  callback_url?: string | null;
  callback_method?: string;
  status_column?: string;
  columns?: ModuleColumnPayload[];
  statuses?: ModuleStatusPayload[];
}

export interface FieldPayload {
  label: string;
  key?: string;
  type: string;
  required?: boolean;
  default_value?: string | null;
  options?: string[] | null;
  order?: number;
  show_in_card?: boolean;
  show_in_list?: boolean;
  visible?: boolean;
}

export interface LayoutFieldPayload {
  id: string;
  order?: number;
  show_in_card?: boolean;
  show_in_list?: boolean;
  show_in_detail?: boolean;
  highlighted?: boolean;
  visible?: boolean;
}

export interface LayoutPayload {
  fields: LayoutFieldPayload[];
  detail_layout?: import('@/types').DetailLayout | null;
}

export const DEFAULT_MODULE_STATUSES: ModuleStatusPayload[] = [
  { slug: 'inputar', label: 'Inputar', order: 0, external_value: 'Inputar' },
  { slug: 'em_andamento', label: 'Em Andamento', order: 1, external_value: 'Em Andamento' },
  { slug: 'aprovados', label: 'Aprovados', order: 2, external_value: 'Aprovados' },
  { slug: 'reprovados', label: 'Reprovados', order: 3, external_value: 'Reprovados' },
];

export const modulesService = {
  async list(page = 1, perPage = 50): Promise<Paginated<Module>> {
    const { data } = await http.get<Paginated<Module>>('/modules', {
      params: { page, per_page: perPage },
    });
    return data;
  },

  async allowed(): Promise<Module[]> {
    const { data } = await http.get<{ data: Module[] }>('/me/modules');
    return data.data;
  },

  async get(id: string): Promise<Module> {
    const { data } = await http.get<{ data: Module }>(`/modules/${id}`);
    return data.data;
  },

  async create(payload: ModulePayload): Promise<Module> {
    const { data } = await http.post<{ data: Module }>('/modules', payload);
    return data.data;
  },

  async update(id: string, payload: Partial<ModulePayload>): Promise<Module> {
    const { data } = await http.put<{ data: Module }>(`/modules/${id}`, payload);
    return data.data;
  },

  async remove(id: string): Promise<void> {
    await http.delete(`/modules/${id}`);
  },

  async updateLayout(id: string, payload: LayoutPayload): Promise<Module> {
    const { data } = await http.put<{ data: Module }>(`/modules/${id}/layout`, payload);
    return data.data;
  },

  async listFields(moduleId: string): Promise<ModuleField[]> {
    const { data } = await http.get<{ data: ModuleField[] }>(`/modules/${moduleId}/fields`);
    return data.data;
  },

  async createField(moduleId: string, payload: FieldPayload): Promise<ModuleField> {
    const { data } = await http.post<{ data: ModuleField }>(`/modules/${moduleId}/fields`, payload);
    return data.data;
  },

  async updateField(fieldId: string, payload: Partial<FieldPayload>): Promise<ModuleField> {
    const { data } = await http.put<{ data: ModuleField }>(`/fields/${fieldId}`, payload);
    return data.data;
  },

  async removeField(fieldId: string): Promise<void> {
    await http.delete(`/fields/${fieldId}`);
  },
};
