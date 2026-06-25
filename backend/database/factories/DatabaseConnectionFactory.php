<?php

namespace Database\Factories;

use App\Models\DatabaseConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DatabaseConnection>
 */
class DatabaseConnectionFactory extends Factory
{
    protected $model = DatabaseConnection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'mystique_test',
            'username' => 'root',
            'password' => 'secret',
            'table_name' => 'pedidos',
        ];
    }
}
