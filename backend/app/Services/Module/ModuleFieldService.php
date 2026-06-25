<?php

namespace App\Services\Module;

use App\Enums\ActivityAction;
use App\Models\Module;
use App\Models\ModuleField;
use App\Services\ActivityLog\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ModuleFieldService
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @return Collection<int, ModuleField>
     */
    public function listForModule(Module $module): Collection
    {
        return $module->fields()->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Module $module, array $data): ModuleField
    {
        $field = $module->fields()->create([
            'label' => $data['label'],
            'key' => $data['key'] ?? Str::slug($data['label'], '_'),
            'type' => $data['type'],
            'required' => $data['required'] ?? false,
            'default_value' => $data['default_value'] ?? null,
            'options' => $data['options'] ?? null,
            'order' => $data['order'] ?? ($module->fields()->max('order') + 1),
            'show_in_card' => $data['show_in_card'] ?? false,
            'show_in_list' => $data['show_in_list'] ?? true,
            'visible' => $data['visible'] ?? true,
        ]);

        $this->logger->log(
            ActivityAction::MODULE_FIELD_CREATED,
            "Campo '{$field->label}' criado no módulo '{$module->name}'.",
            subject: $field,
        );

        return $field;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ModuleField $field, array $data): ModuleField
    {
        $field->update([
            'label' => $data['label'] ?? $field->label,
            'key' => $data['key'] ?? $field->key,
            'type' => $data['type'] ?? $field->type->value,
            'required' => $data['required'] ?? $field->required,
            'default_value' => array_key_exists('default_value', $data) ? $data['default_value'] : $field->default_value,
            'options' => array_key_exists('options', $data) ? $data['options'] : $field->options,
            'order' => $data['order'] ?? $field->order,
            'show_in_card' => $data['show_in_card'] ?? $field->show_in_card,
            'show_in_list' => $data['show_in_list'] ?? $field->show_in_list,
            'visible' => $data['visible'] ?? $field->visible,
        ]);

        $this->logger->log(
            ActivityAction::MODULE_FIELD_UPDATED,
            "Campo '{$field->label}' atualizado.",
            subject: $field,
        );

        return $field;
    }

    public function delete(ModuleField $field): void
    {
        $label = $field->label;

        $field->delete();

        $this->logger->log(
            ActivityAction::MODULE_FIELD_DELETED,
            "Campo '{$label}' removido.",
        );
    }
}
