<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordValue extends Model
{
    protected $fillable = [
        'record_id',
        'field_id',
        'value',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(ModuleRecord::class, 'record_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(ModuleField::class, 'field_id');
    }
}
