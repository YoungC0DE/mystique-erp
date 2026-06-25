<?php

namespace App\Http\Resources;

use App\Enums\FieldType;
use App\Models\ModuleRecord;
use App\Models\RecordValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ModuleRecord
 */
class RecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'status' => $this->status,
            'values' => $this->whenLoaded('values', fn () => $this->mapValues()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapValues(): array
    {
        return $this->values->mapWithKeys(function (RecordValue $value) {
            $field = $value->field;

            if (! $field) {
                return [];
            }

            return [$field->key => $this->castValue($field->type, $value->value)];
        })->all();
    }

    private function castValue(FieldType $type, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            FieldType::BOOLEAN => (bool) $value,
            FieldType::NUMBER => (int) $value,
            FieldType::DECIMAL => (float) $value,
            FieldType::MULTISELECT => json_decode($value, true) ?? [],
            default => $value,
        };
    }
}
