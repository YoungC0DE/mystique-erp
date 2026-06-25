<?php

namespace App\Http\Resources;

use App\Models\ModuleField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ModuleField
 */
class ModuleFieldResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'label' => $this->label,
            'key' => $this->key,
            'type' => $this->type,
            'required' => $this->required,
            'default_value' => $this->default_value,
            'options' => $this->options,
            'order' => $this->order,
            'show_in_card' => $this->show_in_card,
            'show_in_list' => $this->show_in_list,
            'show_in_detail' => $this->show_in_detail,
            'highlighted' => $this->highlighted,
            'visible' => $this->visible,
        ];
    }
}
