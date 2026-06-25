<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\ModuleRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModuleRecord>
 */
class ModuleRecordFactory extends Factory
{
    protected $model = ModuleRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'status' => 'inputar',
            'created_by' => null,
        ];
    }

    public function status(string $slug): static
    {
        return $this->state(fn () => ['status' => $slug]);
    }

    public function forModule(Module $module): static
    {
        return $this->state(fn () => [
            'module_id' => $module->id,
        ]);
    }
}
