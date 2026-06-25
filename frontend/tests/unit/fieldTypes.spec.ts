import { describe, expect, it } from 'vitest';
import { FIELD_TYPES, TYPES_WITH_OPTIONS, fieldTypeLabel } from '@/constants/fieldTypes';

describe('fieldTypes', () => {
  it('exposes the 11 supported field types', () => {
    expect(FIELD_TYPES).toHaveLength(11);
  });

  it('flags only select and multiselect as types with options', () => {
    expect(TYPES_WITH_OPTIONS).toEqual(['select', 'multiselect']);
  });

  it('returns the human label for a known type', () => {
    expect(fieldTypeLabel('multiselect')).toBe('Seleção (múltipla)');
    expect(fieldTypeLabel('boolean')).toBe('Sim/Não');
  });

  it('falls back to the raw value for an unknown type', () => {
    // @ts-expect-error testando entrada inválida em runtime
    expect(fieldTypeLabel('inexistente')).toBe('inexistente');
  });
});
