<?php

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\Module;
use App\Models\ModuleField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModuleField>
 */
class ModuleFieldFactory extends Factory
{
    protected $model = ModuleField::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->word();

        return [
            'module_id' => Module::factory(),
            'label' => ucfirst($key),
            'key' => $key,
            'type' => FieldType::TEXT->value,
            'required' => false,
            'default_value' => null,
            'options' => null,
            'order' => 0,
            'show_in_card' => true,
            'show_in_list' => true,
            'show_in_detail' => true,
            'highlighted' => false,
            'visible' => true,
        ];
    }

    public function ofType(FieldType $type): static
    {
        return $this->state(fn () => ['type' => $type->value]);
    }

    public function required(): static
    {
        return $this->state(fn () => ['required' => true]);
    }

    public function forModule(Module $module): static
    {
        return $this->state(fn () => ['module_id' => $module->id]);
    }
}
