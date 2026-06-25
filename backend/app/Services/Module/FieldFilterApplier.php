<?php

namespace App\Services\Module;

use App\Enums\FieldType;
use App\Enums\FilterOperator;
use App\Models\Module;
use App\Models\ModuleField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FieldFilterApplier
{
    /**
     * @param  list<array<string, mixed>>  $filters
     */
    public function applyToExternalQuery(
        QueryBuilder $query,
        Module $module,
        array $filters,
    ): void {
        $fieldsByKey = $module->fields->keyBy('key');

        foreach ($filters as $index => $filter) {
            $this->applyExternalFilter($query, $fieldsByKey, $filter, $index);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $filters
     */
    public function applyToEavQuery(
        Builder $query,
        Module $module,
        array $filters,
    ): void {
        $fieldsByKey = $module->fields->keyBy('key');

        foreach ($filters as $index => $filter) {
            $this->applyEavFilter($query, $fieldsByKey, $filter, $index);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rawFilters
     * @return list<array<string, mixed>>
     */
    public function normalize(array $rawFilters, Module $module): array
    {
        $module->loadMissing('fields');
        $fieldsByKey = $module->fields->keyBy('key');
        $normalized = [];

        foreach ($rawFilters as $index => $filter) {
            if (! is_array($filter)) {
                continue;
            }

            $fieldKey = (string) ($filter['field'] ?? '');
            $operator = (string) ($filter['operator'] ?? '');
            $field = $fieldsByKey->get($fieldKey);

            if ($field === null) {
                throw ValidationException::withMessages([
                    "filters.{$index}.field" => [__('modules.filter_unknown_field', ['field' => $fieldKey])],
                ]);
            }

            $allowed = FilterOperator::forFieldType($field->type);

            if (! in_array($operator, $allowed, true)) {
                throw ValidationException::withMessages([
                    "filters.{$index}.operator" => [__('modules.filter_invalid_operator')],
                ]);
            }

            $normalized[] = [
                'field' => $fieldKey,
                'operator' => $operator,
                'value' => $filter['value'] ?? null,
                'value_to' => $filter['value_to'] ?? null,
            ];
        }

        return $normalized;
    }

    /**
     * @param  Collection<string, ModuleField>  $fieldsByKey
     * @param  array<string, mixed>  $filter
     */
    private function applyExternalFilter(
        QueryBuilder $query,
        Collection $fieldsByKey,
        array $filter,
        int $index,
    ): void {
        $fieldKey = (string) ($filter['field'] ?? '');
        $operator = FilterOperator::tryFrom((string) ($filter['operator'] ?? ''));

        if ($operator === null) {
            return;
        }

        $field = $fieldsByKey->get($fieldKey);

        if ($field === null) {
            return;
        }

        $this->assertSafeIdentifier($fieldKey, "filters.{$index}.field");

        $value = $filter['value'] ?? null;
        $valueTo = $filter['value_to'] ?? null;

        match ($operator) {
            FilterOperator::EQ => $query->where($fieldKey, '=', $this->castFilterValue($field->type, $value)),
            FilterOperator::NEQ => $query->where($fieldKey, '!=', $this->castFilterValue($field->type, $value)),
            FilterOperator::CONTAINS => $query->where($fieldKey, 'like', '%'.$this->stringValue($value).'%'),
            FilterOperator::NOT_CONTAINS => $query->where($fieldKey, 'not like', '%'.$this->stringValue($value).'%'),
            FilterOperator::STARTS_WITH => $query->where($fieldKey, 'like', $this->stringValue($value).'%'),
            FilterOperator::ENDS_WITH => $query->where($fieldKey, 'like', '%'.$this->stringValue($value)),
            FilterOperator::GT => $query->where($fieldKey, '>', $this->castFilterValue($field->type, $value)),
            FilterOperator::LT => $query->where($fieldKey, '<', $this->castFilterValue($field->type, $value)),
            FilterOperator::GTE => $query->where($fieldKey, '>=', $this->castFilterValue($field->type, $value)),
            FilterOperator::LTE => $query->where($fieldKey, '<=', $this->castFilterValue($field->type, $value)),
            FilterOperator::BETWEEN => $query->whereBetween($fieldKey, [
                $this->castFilterValue($field->type, $value),
                $this->castFilterValue($field->type, $valueTo),
            ]),
            FilterOperator::BEFORE => $query->where($fieldKey, '<', $this->castFilterValue($field->type, $value)),
            FilterOperator::AFTER => $query->where($fieldKey, '>', $this->castFilterValue($field->type, $value)),
            FilterOperator::IN => $query->whereIn($fieldKey, $this->listValue($value)),
        };
    }

    /**
     * @param  Collection<string, ModuleField>  $fieldsByKey
     * @param  array<string, mixed>  $filter
     */
    private function applyEavFilter(
        Builder $query,
        Collection $fieldsByKey,
        array $filter,
        int $index,
    ): void {
        $fieldKey = (string) ($filter['field'] ?? '');
        $operator = FilterOperator::tryFrom((string) ($filter['operator'] ?? ''));

        if ($operator === null) {
            return;
        }

        $field = $fieldsByKey->get($fieldKey);

        if ($field === null) {
            return;
        }

        $value = $filter['value'] ?? null;
        $valueTo = $filter['value_to'] ?? null;
        $fieldId = $field->getKey();

        $query->whereHas('values', function (Builder $values) use (
            $fieldId,
            $operator,
            $field,
            $value,
            $valueTo,
        ) {
            $values->where('field_id', $fieldId);

            match ($operator) {
                FilterOperator::EQ => $values->where('value', '=', $this->stringValue($this->castFilterValue($field->type, $value))),
                FilterOperator::NEQ => $values->where('value', '!=', $this->stringValue($this->castFilterValue($field->type, $value))),
                FilterOperator::CONTAINS => $values->where('value', 'like', '%'.$this->stringValue($value).'%'),
                FilterOperator::NOT_CONTAINS => $values->where('value', 'not like', '%'.$this->stringValue($value).'%'),
                FilterOperator::STARTS_WITH => $values->where('value', 'like', $this->stringValue($value).'%'),
                FilterOperator::ENDS_WITH => $values->where('value', 'like', '%'.$this->stringValue($value)),
                FilterOperator::GT => $values->whereRaw('CAST(value AS DECIMAL(20,4)) > ?', [(float) $value]),
                FilterOperator::LT => $values->whereRaw('CAST(value AS DECIMAL(20,4)) < ?', [(float) $value]),
                FilterOperator::GTE => $values->whereRaw('CAST(value AS DECIMAL(20,4)) >= ?', [(float) $value]),
                FilterOperator::LTE => $values->whereRaw('CAST(value AS DECIMAL(20,4)) <= ?', [(float) $value]),
                FilterOperator::BETWEEN => $values->whereRaw(
                    'CAST(value AS DECIMAL(20,4)) BETWEEN ? AND ?',
                    [(float) $value, (float) $valueTo]
                ),
                FilterOperator::BEFORE => $values->where('value', '<', $this->stringValue($value)),
                FilterOperator::AFTER => $values->where('value', '>', $this->stringValue($value)),
                FilterOperator::IN => $values->whereIn('value', $this->listValue($value)),
            };
        });
    }

    private function castFilterValue(FieldType $type, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return match ($type) {
            FieldType::BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            FieldType::NUMBER => (int) $value,
            FieldType::DECIMAL => (float) $value,
            default => (string) $value,
        };
    }

    private function stringValue(mixed $value): string
    {
        return (string) ($value ?? '');
    }

    /**
     * @return list<string>
     */
    private function listValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_map(fn ($v) => (string) $v, $value));
        }

        if (is_string($value) && str_contains($value, ',')) {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return $value !== null && $value !== '' ? [(string) $value] : [];
    }

    private function assertSafeIdentifier(string $name, string $field): void
    {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw ValidationException::withMessages([
                $field => [__('modules.invalid_identifier', ['name' => $name])],
            ]);
        }
    }
}
