<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleRecord extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'module_id',
        'status',
        'created_by',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(RecordValue::class, 'record_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(RecordAudit::class, 'record_id')->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
