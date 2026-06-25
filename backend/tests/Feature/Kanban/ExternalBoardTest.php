<?php

namespace Tests\Feature\Kanban;

use App\Enums\FieldType;
use App\Models\DatabaseConnection;
use App\Models\Module;
use App\Models\ModuleField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExternalBoardTest extends TestCase
{
    use RefreshDatabase;

    private const TABLE = 'external_board_fixture';

    private User $user;

    private DatabaseConnection $connection;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->userWithPermissions(['read']);

        $this->createFixtureTable();
        $this->seedFixtureData();

        $this->connection = DatabaseConnection::factory()->create([
            'host' => Config::string('database.connections.mysql.host', '127.0.0.1'),
            'port' => (int) Config::string('database.connections.mysql.port', '3306'),
            'database' => Config::string('database.connections.mysql.database', 'mystique_test'),
            'username' => Config::string('database.connections.mysql.username', 'root'),
            'password' => Config::string('database.connections.mysql.password', ''),
            'table_name' => self::TABLE,
        ]);

        $this->module = Module::factory()->create([
            'connection_id' => $this->connection->id,
            'status_column' => 'status',
            'callback_url' => 'https://example.com/callback',
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

    public function test_board_reads_external_table_grouped_by_status(): void
    {
        $response = $this->actingAsApi($this->user)
            ->getJson("/api/modules/{$this->module->uuid}/kanban")
            ->assertOk();

        $columns = collect($response->json('columns'))->keyBy('key');

        $this->assertSame(2, $columns['inputar']['meta']['total']);
        $this->assertSame(1, $columns['em_andamento']['meta']['total']);
        $this->assertSame(1, $columns['aprovados']['meta']['total']);
        $this->assertSame(0, $columns['reprovados']['meta']['total']);

        $first = $columns['inputar']['records'][0];
        $this->assertArrayHasKey('id', $first);
        $this->assertSame('inputar', $first['status']);
        $this->assertArrayHasKey('cliente', $first['values']);
    }

    public function test_board_paginates_each_status_independently(): void
    {
        DB::table(self::TABLE)->insert([
            ['cliente' => 'Extra A', 'status' => 'Inputar'],
            ['cliente' => 'Extra B', 'status' => 'Inputar'],
        ]);

        $response = $this->actingAsApi($this->user)
            ->getJson("/api/modules/{$this->module->uuid}/kanban?per_page=2")
            ->assertOk();

        $inputar = collect($response->json('columns'))->firstWhere('key', 'inputar');

        $this->assertCount(2, $inputar['records']);
        $this->assertSame(2, $inputar['meta']['last_page']);
        $this->assertSame(4, $inputar['meta']['total']);
    }

    public function test_board_filters_by_search_term(): void
    {
        $response = $this->actingAsApi($this->user)
            ->getJson("/api/modules/{$this->module->uuid}/kanban?q=Especial")
            ->assertOk();

        $inputar = collect($response->json('columns'))->firstWhere('key', 'inputar');

        $this->assertSame(1, $inputar['meta']['total']);
        $this->assertSame('Cliente Especial', $inputar['records'][0]['values']['cliente']);
    }

    public function test_board_uses_module_status_slugs_as_column_keys(): void
    {
        $response = $this->actingAsApi($this->user)
            ->getJson("/api/modules/{$this->module->uuid}/kanban")
            ->assertOk();

        $keys = collect($response->json('columns'))->pluck('key')->all();

        $this->assertEqualsCanonicalizing(
            ['inputar', 'em_andamento', 'aprovados', 'reprovados'],
            $keys,
        );
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
            ['cliente' => 'Cliente Especial', 'status' => 'Inputar'],
            ['cliente' => 'Cliente B', 'status' => 'Em Andamento'],
            ['cliente' => 'Cliente C', 'status' => 'Aprovados'],
        ]);
    }
}
