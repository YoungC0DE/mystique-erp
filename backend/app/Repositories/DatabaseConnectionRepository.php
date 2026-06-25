<?php

namespace App\Repositories;

use App\Models\DatabaseConnection;
use Illuminate\Database\Eloquent\Collection;

class DatabaseConnectionRepository
{
    public function all(): Collection
    {
        return DatabaseConnection::query()->orderBy('name')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DatabaseConnection
    {
        return DatabaseConnection::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DatabaseConnection $connection, array $data): DatabaseConnection
    {
        $connection->update($data);

        return $connection;
    }

    public function delete(DatabaseConnection $connection): void
    {
        $connection->delete();
    }
}
