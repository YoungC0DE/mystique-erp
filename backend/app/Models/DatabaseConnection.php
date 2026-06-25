<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Database\Factories\DatabaseConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatabaseConnection extends Model
{
    /** @use HasFactory<DatabaseConnectionFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'host',
        'port',
        'database',
        'username',
        'password',
        'table_name',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'password' => 'encrypted',
        ];
    }
}
