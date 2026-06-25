<?php

namespace App\Enums;

enum FilterOperator: string
{
    case EQ = 'eq';
    case NEQ = 'neq';
    case CONTAINS = 'contains';
    case NOT_CONTAINS = 'not_contains';
    case STARTS_WITH = 'starts_with';
    case ENDS_WITH = 'ends_with';
    case GT = 'gt';
    case LT = 'lt';
    case GTE = 'gte';
    case LTE = 'lte';
    case BETWEEN = 'between';
    case BEFORE = 'before';
    case AFTER = 'after';
    case IN = 'in';

    /**
     * @return list<string>
     */
    public static function forFieldType(FieldType $type): array
    {
        return match ($type) {
            FieldType::NUMBER, FieldType::DECIMAL => [
                self::EQ->value,
                self::NEQ->value,
                self::GT->value,
                self::LT->value,
                self::GTE->value,
                self::LTE->value,
                self::BETWEEN->value,
            ],
            FieldType::DATE, FieldType::DATETIME => [
                self::EQ->value,
                self::BEFORE->value,
                self::AFTER->value,
                self::BETWEEN->value,
            ],
            FieldType::BOOLEAN => [
                self::EQ->value,
            ],
            FieldType::SELECT, FieldType::MULTISELECT => [
                self::EQ->value,
                self::NEQ->value,
                self::IN->value,
            ],
            default => [
                self::EQ->value,
                self::NEQ->value,
                self::CONTAINS->value,
                self::NOT_CONTAINS->value,
                self::STARTS_WITH->value,
                self::ENDS_WITH->value,
            ],
        };
    }
}
