<?php

namespace Tests\Feature\Module;

use App\Enums\FieldType;
use App\Models\Module;
use App\Models\ModuleField;
use App\Models\ModuleRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordEavTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->userWithPermissions(['create', 'read', 'update', 'delete']);
        $this->module = Module::factory()->create();

        ModuleField::factory()->forModule($this->module)->required()->create([
            'label' => 'Cliente', 'key' => 'cliente', 'type' => FieldType::TEXT->value, 'order' => 1,
        ]);
        ModuleField::factory()->forModule($this->module)->ofType(FieldType::DECIMAL)->create([
            'label' => 'Valor', 'key' => 'valor', 'order' => 2,
        ]);
        ModuleField::factory()->forModule($this->module)->ofType(FieldType::BOOLEAN)->create([
            'label' => 'Ativo', 'key' => 'ativo', 'order' => 3,
        ]);
        ModuleField::factory()->forModule($this->module)->ofType(FieldType::MULTISELECT)->create([
            'label' => 'Tags', 'key' => 'tags', 'order' => 4,
            'options' => ['vip', 'novo', 'recorrente'],
        ]);
    }

    public function test_create_record_persists_eav_values_and_audit(): void
    {
        $response = $this->actingAsApi($this->user)
            ->postJson("/api/modules/{$this->module->uuid}/records", [
                'values' => [
                    'cliente' => 'ACME',
                    'valor' => 1234.56,
                    'ativo' => true,
                    'tags' => ['vip', 'novo'],
                ],
            ])
            ->assertCreated();

        $recordUuid = $response->json('data.id');
        $record = ModuleRecord::where('uuid', $recordUuid)->firstOrFail();

        $this->assertDatabaseHas('record_values', [
            'record_id' => $record->id,
            'value' => 'ACME',
        ]);
        $this->assertDatabaseHas('record_values', [
            'record_id' => $record->id,
            'value' => '1',
        ]);
        $this->assertDatabaseHas('record_values', [
            'record_id' => $record->id,
            'value' => '["vip","novo"]',
        ]);

        $this->assertDatabaseHas('record_audits', [
            'record_id' => $record->id,
            'action' => 'created',
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'record.created']);
    }

    public function test_record_resource_casts_values_by_type(): void
    {
        $response = $this->actingAsApi($this->user)
            ->postJson("/api/modules/{$this->module->uuid}/records", [
                'values' => [
                    'cliente' => 'ACME',
                    'valor' => 99.9,
                    'ativo' => true,
                    'tags' => ['vip'],
                ],
            ])
            ->assertCreated();

        $response->assertJsonPath('data.values.cliente', 'ACME')
            ->assertJsonPath('data.values.valor', 99.9)
            ->assertJsonPath('data.values.ativo', true)
            ->assertJsonPath('data.values.tags', ['vip']);
    }

    public function test_create_record_enforces_required_fields(): void
    {
        $this->actingAsApi($this->user)
            ->postJson("/api/modules/{$this->module->uuid}/records", [
                'values' => ['valor' => 10],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('values.cliente');

        $this->assertDatabaseCount('module_records', 0);
    }

    public function test_update_record_tracks_old_and_new_values_in_audit(): void
    {
        $record = $this->createRecord(['cliente' => 'ACME', 'valor' => 10]);

        $this->actingAsApi($this->user)
            ->putJson("/api/records/{$record->uuid}", [
                'values' => ['cliente' => 'Globex'],
            ])
            ->assertOk()
            ->assertJsonPath('data.values.cliente', 'Globex');

        $audit = $record->audits()->where('action', 'updated')->firstOrFail();
        $this->assertSame('ACME', $audit->changes['cliente']['old']);
        $this->assertSame('Globex', $audit->changes['cliente']['new']);
    }

    public function test_creating_record_requires_create_permission(): void
    {
        $reader = $this->userWithPermissions(['read']);

        $this->actingAsApi($reader)
            ->postJson("/api/modules/{$this->module->uuid}/records", [
                'values' => ['cliente' => 'ACME'],
            ])
            ->assertForbidden();
    }

    public function test_user_can_delete_record(): void
    {
        $record = $this->createRecord(['cliente' => 'ACME']);

        $this->actingAsApi($this->user)
            ->deleteJson("/api/records/{$record->uuid}")
            ->assertOk();

        $this->assertDatabaseMissing('module_records', ['id' => $record->id]);
    }

    public function test_record_audit_history_endpoint(): void
    {
        $record = $this->createRecord(['cliente' => 'ACME']);

        $this->actingAsApi($this->user)
            ->putJson("/api/records/{$record->uuid}", ['values' => ['cliente' => 'Globex']])
            ->assertOk();

        $this->actingAsApi($this->user)
            ->getJson("/api/records/{$record->uuid}/audits")
            ->assertOk()
            ->assertJsonCount(2, 'data'); // created + updated
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function createRecord(array $values): ModuleRecord
    {
        $uuid = $this->actingAsApi($this->user)
            ->postJson("/api/modules/{$this->module->uuid}/records", ['values' => $values])
            ->assertCreated()
            ->json('data.id');

        return ModuleRecord::where('uuid', $uuid)->firstOrFail();
    }
}
