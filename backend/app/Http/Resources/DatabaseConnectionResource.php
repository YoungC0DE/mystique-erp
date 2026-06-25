<?php

namespace App\Http\Resources;

use App\Models\DatabaseConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DatabaseConnection
 */
class DatabaseConnectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'table_name' => $this->table_name,
            'has_password' => ! empty($this->password),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
