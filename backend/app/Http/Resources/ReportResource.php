<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'module_id' => $this->whenLoaded('module', fn () => $this->module->uuid),
            'module' => new ModuleResource($this->whenLoaded('module')),
            'field_keys' => $this->field_keys,
            'filters' => $this->filters ?? [],
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->uuid,
                'name' => $this->creator?->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
