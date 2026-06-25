import { http } from './http'
import type { FieldFilter } from '@/lib/filters'
import { serializeFilters } from '@/lib/filters'
import type { Paginated, Report, ReportRunResult } from '@/types'

export interface ReportPayload {
  name: string
  module_id: string
  field_keys: string[]
  filters?: FieldFilter[]
}

export const reportsService = {
  async list(page = 1, perPage = 50): Promise<Paginated<Report>> {
    const { data } = await http.get<Paginated<Report>>('/reports', {
      params: { page, per_page: perPage },
    })
    return data
  },

  async get(id: string): Promise<Report> {
    const { data } = await http.get<{ data: Report }>(`/reports/${id}`)
    return data.data
  },

  async create(payload: ReportPayload): Promise<Report> {
    const { data } = await http.post<{ data: Report }>('/reports', {
      ...payload,
      filters: payload.filters?.map(({ field, operator, value, value_to }) => ({
        field,
        operator,
        value,
        value_to,
      })),
    })
    return data.data
  },

  async update(id: string, payload: Partial<ReportPayload>): Promise<Report> {
    const { data } = await http.put<{ data: Report }>(`/reports/${id}`, {
      ...payload,
      filters: payload.filters?.map(({ field, operator, value, value_to }) => ({
        field,
        operator,
        value,
        value_to,
      })),
    })
    return data.data
  },

  async remove(id: string): Promise<void> {
    await http.delete(`/reports/${id}`)
  },

  async run(id: string, page = 1, perPage = 25): Promise<ReportRunResult> {
    const { data } = await http.get<ReportRunResult>(`/reports/${id}/run`, {
      params: { page, per_page: perPage },
    })
    return data
  },
}

export { serializeFilters }
