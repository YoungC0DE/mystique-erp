<?php

namespace App\Enums;

enum FieldType: string
{
    case TEXT = 'texto';
    case TEXTAREA = 'textarea';
    case NUMBER = 'numero';
    case DECIMAL = 'decimal';
    case BOOLEAN = 'boolean';
    case EMAIL = 'email';
    case PHONE = 'telefone';
    case DATE = 'data';
    case DATETIME = 'datetime';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';

    /**
     * Tipos que exigem lista de opções.
     *
     * @return array<int, self>
     */
    public static function withOptions(): array
    {
        return [self::SELECT, self::MULTISELECT];
    }

    public function requiresOptions(): bool
    {
        return in_array($this, self::withOptions(), true);
    }
}
