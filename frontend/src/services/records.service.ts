import { http } from './http'
import type { KanbanBoard, ModuleRecord, Paginated, RecordAudit, RecordNote, RecordValue } from '@/types'
import type { FieldFilter } from '@/lib/filters'
import { serializeFilters } from '@/lib/filters'

export interface RecordPayload {
  status?: string
  values?: Record<string, RecordValue>
}

export interface KanbanFilters {
  q?: string
  created_by?: string
  per_page?: number
  filters?: FieldFilter[]
  // Paginação independente por coluna: ex. inputar_page, em_andamento_page...
  [key: `${string}_page`]: number | undefined
}
export const recordsService = {
  async list(moduleId: string, params: { status?: string; page?: number; per_page?: number } = {}): Promise<Paginated<ModuleRecord>> {
    const { data } = await http.get<Paginated<ModuleRecord>>(`/modules/${moduleId}/records`, { params })
    return data
  },

  async get(recordId: string): Promise<ModuleRecord> {
    const { data } = await http.get<{ data: ModuleRecord }>(`/records/${recordId}`)
    return data.data
  },

  async create(moduleId: string, payload: RecordPayload): Promise<ModuleRecord> {
    const { data } = await http.post<{ data: ModuleRecord }>(`/modules/${moduleId}/records`, payload)
    return data.data
  },

  async update(recordId: string, payload: RecordPayload): Promise<ModuleRecord> {
    const { data } = await http.put<{ data: ModuleRecord }>(`/records/${recordId}`, payload)
    return data.data
  },

  async remove(recordId: string): Promise<void> {
    await http.delete(`/records/${recordId}`)
  },

  async move(recordId: string, status: string): Promise<ModuleRecord> {
    const { data } = await http.put<{ data: ModuleRecord }>(`/records/${recordId}/move`, { status })
    return data.data
  },

  async moveIntegrated(moduleId: string, externalId: string, status: string): Promise<ModuleRecord> {
    const { data } = await http.post<{ data: ModuleRecord }>(
      `/modules/${moduleId}/records/${externalId}/move`,
      { status },
    )
    return data.data
  },

  async audits(recordId: string): Promise<RecordAudit[]> {
    const { data } = await http.get<{ data: RecordAudit[] }>(`/records/${recordId}/audits`)
    return data.data
  },

  async kanban(
    moduleId: string,
    filters: KanbanFilters = {},
    options: { skipGlobalLoading?: boolean } = {},
  ): Promise<KanbanBoard> {
    const { filters: fieldFilters, ...rest } = filters
    const query: Record<string, unknown> = { ...rest }
    if (fieldFilters?.length) {
      query.filters = serializeFilters(fieldFilters)
    }
    const { data } = await http.get<KanbanBoard>(`/modules/${moduleId}/kanban`, {
      params: query,
      skipGlobalLoading: options.skipGlobalLoading,
    })
    return data
  },

  async getNote(moduleId: string, recordKey: string): Promise<RecordNote> {
    const { data } = await http.get<{ data: RecordNote }>(
      `/modules/${moduleId}/records/${recordKey}/note`,
    )
    return data.data
  },

  async saveNote(moduleId: string, recordKey: string, body: string | null): Promise<RecordNote> {
    const { data } = await http.put<{ data: RecordNote }>(
      `/modules/${moduleId}/records/${recordKey}/note`,
      { body },
    )
    return data.data
  },
}
