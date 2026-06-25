<?php

namespace App\Services\ActivityLog;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Registra uma ação no log de atividades.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(
        ActivityAction $action,
        ?string $description = null,
        array $properties = [],
        ?Model $subject = null,
        ?User $user = null,
    ): ActivityLog {
        $user ??= auth()->user();

        return ActivityLog::create([
            'user_id' => $user?->getKey(),
            'action' => $action->value,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
        ]);
    }
}
