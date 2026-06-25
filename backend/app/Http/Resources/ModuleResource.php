<?php

namespace App\Http\Resources;

use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Module
 */
class ModuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'status' => $this->status,
            'is_integrated' => $this->isIntegrated(),
            'connection_id' => $this->whenLoaded('connection', fn () => $this->connection?->uuid),
            'connection' => new DatabaseConnectionResource($this->whenLoaded('connection')),
            'callback_url' => $this->callback_url,
            'callback_method' => $this->callback_method,
            'status_column' => $this->status_column,
            'detail_layout' => $this->detail_layout,
            'statuses' => ModuleKanbanStatusResource::collection($this->whenLoaded('kanbanStatuses')),
            'fields_count' => $this->whenCounted('fields'),
            'fields' => ModuleFieldResource::collection($this->whenLoaded('fields')),
            'created_at' => $this->created_at,
        ];
    }
}
