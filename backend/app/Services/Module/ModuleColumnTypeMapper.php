<?php

namespace App\Services\Module;

use App\Enums\FieldType;

class ModuleColumnTypeMapper
{
    public static function fromDatabaseType(string $type): FieldType
    {
        $normalized = strtolower($type);

        return match (true) {
            str_contains($normalized, 'int') => FieldType::NUMBER,
            str_contains($normalized, 'decimal'),
            str_contains($normalized, 'float'),
            str_contains($normalized, 'double'),
            str_contains($normalized, 'numeric') => FieldType::DECIMAL,
            str_contains($normalized, 'datetime'),
            str_contains($normalized, 'timestamp') => FieldType::DATETIME,
            str_contains($normalized, 'date') => FieldType::DATE,
            str_contains($normalized, 'bool'),
            $normalized === 'tinyint(1)' => FieldType::BOOLEAN,
            default => FieldType::TEXT,
        };
    }
}
