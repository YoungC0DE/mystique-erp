<?php

namespace Tests\Feature\Kanban;

use App\Enums\FieldType;
use App\Events\RecordMoved;
use App\Models\DatabaseConnection;
use App\Models\Module;
use App\Models\ModuleField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StageCallbackTest extends TestCase
{
    use RefreshDatabase;

    private const TABLE = 'external_callback_fixture';

    private User $user;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->userWithPermissions(['read', 'update']);

        $this->createFixtureTable();
        $this->seedFixtureData();

        $connection = DatabaseConnection::factory()->create([
            'host' => Config::string('database.connections.mysql.host', '127.0.0.1'),
            'port' => (int) Config::string('database.connections.mysql.port', '3306'),
            'database' => Config::string('database.connections.mysql.database', 'mystique_test'),
            'username' => Config::string('database.connections.mysql.username', 'root'),
            'password' => Config::string('database.connections.mysql.password', ''),
            'table_name' => self::TABLE,
        ]);

        $this->module = Module::factory()->create([
            'slug' => 'pedidos-callback',
            'connection_id' => $connection->id,
            'status_column' => 'status',
            'callback_url' => 'https://example.com/stage-callback',
            'callback_method' => 'POST',
        ]);

        ModuleField::factory()->forModule($this->module)->create([
            'label' => 'Cliente',
            'key' => 'cliente',
            'type' => FieldType::TEXT->value,
            'order' => 0,
            'show_in_card' => true,
        ]);

        ModuleField::factory()->forModule($this->module)->create([
            'label' => 'Status',
            'key' => 'status',
            'type' => FieldType::TEXT->value,
            'order' => 1,
            'show_in_card' => false,
        ]);
    }

    protected function tearDown(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            Schema::drop(self::TABLE);
        }

        parent::tearDown();
    }

    public function test_move_dispatches_callback_and_broadcasts_on_success(): void
    {
        Event::fake([RecordMoved::class]);

        Http::fake([
            'https://example.com/stage-callback' => Http::response(['ok' => true], 200),
        ]);

        $this->actingAsApi($this->user)
            ->postJson("/api/modules/{$this->module->uuid}/records/1/move", [
                'status' => 'em_andamento',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', '1')
            ->assertJsonPath('data.status', 'em_andamento');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/stage-callback'
                && $request['record_id'] === 1
                && $request['status'] === 'Em Andamento'
                && $request['previous_status'] === 'Inputar'
                && $request['module_slug'] === 'pedidos-callback';
        });

        $this->assertDatabaseHas('activity_logs', ['action' => 'record.stage.callback.sent']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'record.stage.callback.success']);

        Event::assertDispatched(RecordMoved::class, function (RecordMoved $event) {
            return $event->recordId === '1'
                && $event->from === 'inputar'
                && $event->to === 'em_andamento'
                && $event->isExternal;
        });

        $this->assertSame('Inputar', DB::table(self::TABLE)->where('id', 1)->value('status'));
    }

    public function test_move_returns_502_when_callback_fails(): void
    {
        Http::fake([
            'https://example.com/stage-callback' => Http::response('error', 500),
        ]);

        $this->actingAsApi($this->user)
            ->postJson("/api/modules/{$this->module->uuid}/records/1/move", [
                'status' => 'em_andamento',
            ])
            ->assertStatus(502)
            ->assertJsonPath('message', __('modules.stage_callback_failed'));

        $this->assertDatabaseHas('activity_logs', ['action' => 'record.stage.callback.failed']);
        $this->assertSame('Inputar', DB::table(self::TABLE)->where('id', 1)->value('status'));
    }

    public function test_move_returns_422_when_callback_rejects_request(): void
    {
        Http::fake([
            'https://example.com/stage-callback' => Http::response(['error' => 'invalid'], 400),
        ]);

        $this->actingAsApi($this->user)
            ->postJson("/api/modules/{$this->module->uuid}/records/1/move", [
                'status' => 'em_andamento',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', __('modules.stage_callback_rejected'));
    }

    public function test_move_rejects_invalid_status(): void
    {
        $this->actingAsApi($this->user)
            ->postJson("/api/modules/{$this->module->uuid}/records/1/move", [
                'status' => 'inexistente',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_move_requires_update_permission(): void
    {
        $reader = $this->userWithPermissions(['read']);

        $this->actingAsApi($reader)
            ->postJson("/api/modules/{$this->module->uuid}/records/1/move", [
                'status' => 'em_andamento',
            ])
            ->assertForbidden();
    }

    public function test_move_returns_404_for_missing_external_record(): void
    {
        Http::fake();

        $this->actingAsApi($this->user)
            ->postJson("/api/modules/{$this->module->uuid}/records/999/move", [
                'status' => 'em_andamento',
            ])
            ->assertNotFound();
    }

    private function createFixtureTable(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            Schema::drop(self::TABLE);
        }

        Schema::create(self::TABLE, function ($table) {
            $table->id();
            $table->string('cliente');
            $table->string('status');
        });
    }

    private function seedFixtureData(): void
    {
        DB::table(self::TABLE)->insert([
            ['cliente' => 'Cliente A', 'status' => 'Inputar'],
            ['cliente' => 'Cliente B', 'status' => 'Em Andamento'],
        ]);
    }
}
