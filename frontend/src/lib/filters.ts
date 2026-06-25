import type { FieldType } from '@/types';

export type FilterOperator =
  | 'eq'
  | 'neq'
  | 'contains'
  | 'not_contains'
  | 'starts_with'
  | 'ends_with'
  | 'gt'
  | 'lt'
  | 'gte'
  | 'lte'
  | 'between'
  | 'before'
  | 'after'
  | 'in';

export interface FieldFilter {
  id: string;
  field: string;
  operator: FilterOperator;
  value: string | number | boolean | string[] | null;
  value_to?: string | number | null;
}

const TEXT_TYPES: FieldType[] = ['texto', 'textarea', 'email', 'telefone'];
const NUMERIC_TYPES: FieldType[] = ['numero', 'decimal'];
const DATE_TYPES: FieldType[] = ['data', 'datetime'];
const SELECT_TYPES: FieldType[] = ['select', 'multiselect'];

export function operatorsForFieldType(type: FieldType): FilterOperator[] {
  if (NUMERIC_TYPES.includes(type)) {
    return ['eq', 'neq', 'gt', 'lt', 'gte', 'lte', 'between'];
  }
  if (DATE_TYPES.includes(type)) {
    return ['eq', 'before', 'after', 'between'];
  }
  if (type === 'boolean') {
    return ['eq'];
  }
  if (SELECT_TYPES.includes(type)) {
    return ['eq', 'neq', 'in'];
  }
  if (TEXT_TYPES.includes(type)) {
    return ['eq', 'neq', 'contains', 'not_contains', 'starts_with', 'ends_with'];
  }
  return ['eq', 'neq', 'contains', 'not_contains', 'starts_with', 'ends_with'];
}

export function operatorNeedsSecondValue(operator: FilterOperator): boolean {
  return operator === 'between';
}

export function operatorNeedsListValue(operator: FilterOperator): boolean {
  return operator === 'in';
}

export function createEmptyFilter(fieldKey = ''): FieldFilter {
  return {
    id: crypto.randomUUID(),
    field: fieldKey,
    operator: 'eq',
    value: '',
    value_to: null,
  };
}

export function serializeFilters(filters: FieldFilter[]): string {
  return JSON.stringify(
    filters
      .filter((f) => f.field)
      .map(({ field, operator, value, value_to }) => ({
        field,
        operator,
        value,
        ...(value_to !== null && value_to !== undefined && value_to !== '' ? { value_to } : {}),
      })),
  );
}

export function parseFilterListValue(raw: string): string[] {
  return raw
    .split(',')
    .map((v) => v.trim())
    .filter(Boolean);
}
