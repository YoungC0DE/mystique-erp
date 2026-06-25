<?php

namespace App\Http\Resources;

use App\Models\ModuleKanbanStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ModuleKanbanStatus
 */
class ModuleKanbanStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'slug' => $this->slug,
            'label' => $this->label,
            'order' => $this->order,
            'external_value' => $this->external_value,
        ];
    }
}
