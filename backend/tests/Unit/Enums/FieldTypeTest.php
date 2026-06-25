<?php

namespace Tests\Unit\Enums;

use App\Enums\FieldType;
use PHPUnit\Framework\TestCase;

class FieldTypeTest extends TestCase
{
    public function test_with_options_returns_only_select_types(): void
    {
        $this->assertSame(
            [FieldType::SELECT, FieldType::MULTISELECT],
            FieldType::withOptions(),
        );
    }

    public function test_select_types_require_options(): void
    {
        $this->assertTrue(FieldType::SELECT->requiresOptions());
        $this->assertTrue(FieldType::MULTISELECT->requiresOptions());
    }

    public function test_non_select_types_do_not_require_options(): void
    {
        $nonSelect = array_filter(
            FieldType::cases(),
            fn (FieldType $type) => ! in_array($type, FieldType::withOptions(), true),
        );

        foreach ($nonSelect as $type) {
            $this->assertFalse($type->requiresOptions(), "{$type->value} should not require options");
        }
    }
}
