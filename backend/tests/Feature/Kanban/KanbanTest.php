<?php

namespace Tests\Feature\Kanban;

use App\Enums\FieldType;
use App\Models\Module;
use App\Models\ModuleField;
use App\Models\ModuleRecord;
use App\Models\User;
use App\Support\DefaultKanbanStatuses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->userWithPermissions(['create', 'read', 'update', 'delete']);
        $this->module = Module::factory()->create();

        ModuleField::factory()->forModule($this->module)->create([
            'label' => 'Cliente', 'key' => 'cliente', 'type' => FieldType::TEXT->value,
        ]);
    }

    public function test_board_returns_module_kanban_statuses_as_columns(): void
    {
        $response = $this->actingAsApi($this->user)
            ->getJson("/api/modules/{$this->module->uuid}/kanban")
            ->assertOk();

        $keys = collect($response->json('columns'))->pluck('key')->all();
        $expected = collect(DefaultKanbanStatuses::definitions())->pluck('slug')->all();

        $this->assertEqualsCanonicalizing($expected, $keys);
    }

    public function test_board_includes_status_color(): void
    {
        $response = $this->actingAsApi($this->user)
            ->getJson("/api/modules/{$this->module->uuid}/kanban")
            ->assertOk();

        $inputar = collect($response->json('columns'))->firstWhere('key', 'inputar');

        $this->assertSame('#94a3b8', $inputar['color']);
    }

    public function test_board_groups_records_by_column(): void
    {
        ModuleRecord::factory()->forModule($this->module)->status('inputar')->count(2)->create();
        ModuleRecord::factory()->forModule($this->module)->status('aprovados')->count(1)->create();

        $response = $this->actingAsApi($this->user)
            ->getJson("/api/modules/{$this->module->uuid}/kanban")
            ->assertOk();

        $columns = collect($response->json('columns'))->keyBy('key');

        $this->assertSame(2, $columns['inputar']['meta']['total']);
        $this->assertSame(1, $columns['aprovados']['meta']['total']);
        $this->assertSame(0, $columns['em_andamento']['meta']['total']);
    }

    public function test_board_paginates_each_column_independently(): void
    {
        ModuleRecord::factory()->forModule($this->module)->status('inputar')->count(3)->create();

        $response = $this->actingAsApi($this->user)
            ->getJson("/api/modules/{$this->module->uuid}/kanban?per_page=2")
            ->assertOk();

        $inputar = collect($response->json('columns'))->firstWhere('key', 'inputar');

        $this->assertCount(2, $inputar['records']);
        $this->assertSame(2, $inputar['meta']['last_page']);
        $this->assertSame(3, $inputar['meta']['total']);
    }

    public function test_move_record_changes_status_and_creates_audit(): void
    {
        $record = ModuleRecord::factory()->forModule($this->module)
            ->status('inputar')->create();

        $this->actingAsApi($this->user)
            ->putJson("/api/records/{$record->uuid}/move", [
                'status' => 'em_andamento',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'em_andamento');

        $this->assertDatabaseHas('module_records', [
            'id' => $record->id,
            'status' => 'em_andamento',
        ]);

        $audit = $record->audits()->where('action', 'moved')->firstOrFail();
        $this->assertSame('inputar', $audit->changes['status']['old']);
        $this->assertSame('em_andamento', $audit->changes['status']['new']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'record.moved']);
    }

    public function test_move_rejects_invalid_status(): void
    {
        $record = ModuleRecord::factory()->forModule($this->module)->create();

        $this->actingAsApi($this->user)
            ->putJson("/api/records/{$record->uuid}/move", ['status' => 'inexistente'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_move_requires_update_permission(): void
    {
        $reader = $this->userWithPermissions(['read']);
        $record = ModuleRecord::factory()->forModule($this->module)->create();

        $this->actingAsApi($reader)
            ->putJson("/api/records/{$record->uuid}/move", [
                'status' => 'aprovados',
            ])
            ->assertForbidden();
    }

    public function test_board_filters_by_search_term(): void
    {
        $matching = ModuleRecord::factory()->forModule($this->module)->status('inputar')->create();
        $field = $this->module->fields()->first();
        $matching->values()->create(['field_id' => $field->id, 'value' => 'ClienteEspecial']);

        ModuleRecord::factory()->forModule($this->module)->status('inputar')->create();

        $response = $this->actingAsApi($this->user)
            ->getJson("/api/modules/{$this->module->uuid}/kanban?q=Especial")
            ->assertOk();

        $inputar = collect($response->json('columns'))->firstWhere('key', 'inputar');

        $this->assertSame(1, $inputar['meta']['total']);
    }
}
