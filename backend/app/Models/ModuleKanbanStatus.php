<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleKanbanStatus extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'module_id',
        'slug',
        'label',
        'order',
        'external_value',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
