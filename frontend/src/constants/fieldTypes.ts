import type { FieldType } from '@/types';

export const FIELD_TYPES: { value: FieldType; label: string }[] = [
  { value: 'texto', label: 'Texto' },
  { value: 'textarea', label: 'Texto longo' },
  { value: 'numero', label: 'Número' },
  { value: 'decimal', label: 'Decimal' },
  { value: 'boolean', label: 'Sim/Não' },
  { value: 'email', label: 'E-mail' },
  { value: 'telefone', label: 'Telefone' },
  { value: 'data', label: 'Data' },
  { value: 'datetime', label: 'Data e hora' },
  { value: 'select', label: 'Seleção (única)' },
  { value: 'multiselect', label: 'Seleção (múltipla)' },
];

export const TYPES_WITH_OPTIONS: FieldType[] = ['select', 'multiselect'];

export function fieldTypeLabel(type: FieldType): string {
  return FIELD_TYPES.find((t) => t.value === type)?.label ?? type;
}
