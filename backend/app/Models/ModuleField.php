<?php

namespace App\Models;

use App\Enums\FieldType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleField extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'module_id',
        'label',
        'key',
        'type',
        'required',
        'default_value',
        'options',
        'order',
        'show_in_card',
        'show_in_list',
        'show_in_detail',
        'highlighted',
        'visible',
    ];

    protected function casts(): array
    {
        return [
            'type' => FieldType::class,
            'required' => 'boolean',
            'options' => 'array',
            'order' => 'integer',
            'show_in_card' => 'boolean',
            'show_in_list' => 'boolean',
            'show_in_detail' => 'boolean',
            'highlighted' => 'boolean',
            'visible' => 'boolean',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
