<?php

namespace App\Models;

use App\Enums\ModuleStatus;
use App\Models\Concerns\HasUuid;
use App\Support\DefaultKanbanStatuses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory, HasUuid;

    protected static function booted(): void
    {
        static::created(function (Module $module) {
            $module->ensureDefaultKanbanStatuses();
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'status',
        'connection_id',
        'callback_url',
        'callback_method',
        'status_column',
        'detail_layout',
    ];

    protected function casts(): array
    {
        return [
            'status' => ModuleStatus::class,
            'detail_layout' => 'array',
        ];
    }

    public function isIntegrated(): bool
    {
        return $this->connection_id !== null;
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class, 'connection_id');
    }

    public function kanbanStatuses(): HasMany
    {
        return $this->hasMany(ModuleKanbanStatus::class)->orderBy('order');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ModuleField::class)->orderBy('order');
    }

    public function records(): HasMany
    {
        return $this->hasMany(ModuleRecord::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * @return list<string>
     */
    public function kanbanStatusSlugs(): array
    {
        return $this->kanbanStatuses->pluck('slug')->all();
    }

    public function defaultStatusSlug(): string
    {
        return $this->kanbanStatuses->sortBy('order')->first()?->slug
            ?? DefaultKanbanStatuses::definitions()[0]['slug'];
    }

    public function ensureDefaultKanbanStatuses(): void
    {
        foreach (DefaultKanbanStatuses::definitions() as $status) {
            $this->kanbanStatuses()->firstOrCreate(
                ['slug' => $status['slug']],
                $status,
            );
        }
    }
}
