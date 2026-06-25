import type { DetailLayout, DetailLayoutRow, ModuleField } from '@/types'

export const MAX_FIELDS_PER_DETAIL_ROW = 3

export function emptyDetailLayout(): DetailLayout {
  return { rows: [] }
}

/** Converte layout legado (groups/columns) para rows. */
export function normalizeDetailLayout(layout: DetailLayout | null | undefined): DetailLayoutRow[] {
  if (layout?.rows?.length) {
    return layout.rows.map((row) => ({
      field_keys: [...row.field_keys].slice(0, MAX_FIELDS_PER_DETAIL_ROW),
    }))
  }

  if (layout?.groups?.length) {
    const rows: DetailLayoutRow[] = []
    for (const group of layout.groups) {
      if (group.field_keys.length === 0) continue
      if (layout.columns === 2 && group.field_keys.length > 1) {
        for (let i = 0; i < group.field_keys.length; i += 2) {
          rows.push({ field_keys: group.field_keys.slice(i, i + 2) })
        }
      } else {
        for (const key of group.field_keys) {
          rows.push({ field_keys: [key] })
        }
      }
    }
    return rows
  }

  return []
}

export function rowsFromFields(fields: ModuleField[]): DetailLayoutRow[] {
  return fields
    .filter((f) => f.show_in_detail && f.visible)
    .sort((a, b) => a.order - b.order)
    .map((f) => ({ field_keys: [f.key] }))
}

export function allKeysInRows(rows: DetailLayoutRow[]): string[] {
  return rows.flatMap((r) => r.field_keys)
}

export function syncFieldsFromDetailRows(
  fields: ModuleField[],
  rows: DetailLayoutRow[],
): ModuleField[] {
  const placed = new Set(allKeysInRows(rows))
  let order = 0

  const orderMap = new Map<string, number>()
  for (const row of rows) {
    for (const key of row.field_keys) {
      orderMap.set(key, order++)
    }
  }

  return fields.map((field) => ({
    ...field,
    show_in_detail: placed.has(field.key),
    order: orderMap.get(field.key) ?? field.order + 1000,
  }))
}

export function rowGridClass(count: number): string {
  if (count >= 3) return 'grid-cols-3'
  if (count === 2) return 'grid-cols-2'
  return 'grid-cols-1'
}
