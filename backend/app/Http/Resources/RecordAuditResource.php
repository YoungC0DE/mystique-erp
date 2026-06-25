<?php

namespace App\Http\Resources;

use App\Models\RecordAudit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RecordAudit
 */
class RecordAuditResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'action' => $this->action,
            'changes' => $this->changes,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->uuid,
                'name' => $this->user?->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
