<?php

namespace Database\Factories;

use App\Enums\ModuleStatus;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'icon' => 'package',
            'status' => ModuleStatus::ACTIVE->value,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ModuleStatus::INACTIVE->value]);
    }
}
