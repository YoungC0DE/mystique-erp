<?php

namespace Tests\Unit\Services;

use App\Enums\FieldType;
use App\Models\ModuleField;
use App\Repositories\RecordRepository;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Module\RecordService;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RecordServiceNormalizeTest extends TestCase
{
    private RecordService $service;

    private ReflectionMethod $normalize;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RecordService(
            Mockery::mock(RecordRepository::class),
            Mockery::mock(ActivityLogger::class),
        );

        $this->normalize = new ReflectionMethod($this->service, 'normalize');
        $this->normalize->setAccessible(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function normalize(FieldType $type, mixed $raw): ?string
    {
        return $this->normalize->invoke($this->service, $this->field($type), $raw);
    }

    private function field(FieldType $type): ModuleField
    {
        $field = new ModuleField;
        $field->type = $type;

        return $field;
    }

    public function test_null_and_empty_string_become_null(): void
    {
        $this->assertNull($this->normalize(FieldType::TEXT, null));
        $this->assertNull($this->normalize(FieldType::TEXT, ''));
    }

    public function test_boolean_values_are_normalized_to_flags(): void
    {
        $this->assertSame('1', $this->normalize(FieldType::BOOLEAN, true));
        $this->assertSame('1', $this->normalize(FieldType::BOOLEAN, 'true'));
        $this->assertSame('1', $this->normalize(FieldType::BOOLEAN, '1'));
        $this->assertSame('0', $this->normalize(FieldType::BOOLEAN, '0'));
        $this->assertSame('0', $this->normalize(FieldType::BOOLEAN, 'false'));
    }

    public function test_multiselect_is_stored_as_json_array(): void
    {
        $this->assertSame('["a","b"]', $this->normalize(FieldType::MULTISELECT, ['a', 'b']));
        $this->assertSame('["a"]', $this->normalize(FieldType::MULTISELECT, 'a'));
    }

    public function test_scalar_values_are_cast_to_string(): void
    {
        $this->assertSame('5', $this->normalize(FieldType::NUMBER, 5));
        $this->assertSame('10.5', $this->normalize(FieldType::DECIMAL, 10.5));
        $this->assertSame('hello', $this->normalize(FieldType::TEXT, 'hello'));
    }

    public function test_arrays_on_non_multiselect_fields_are_json_encoded(): void
    {
        $this->assertSame('{"a":1}', $this->normalize(FieldType::TEXT, ['a' => 1]));
    }
}
