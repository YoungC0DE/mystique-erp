<?php

namespace App\Services\Module;

use App\Enums\ModuleStatus;
use App\Models\Module;

class ModuleProvisioner
{
    /**
     * Provisiona o módulo padrão "Pedidos" na instalação (single-tenant).
     */
    public function provisionDefaultModules(): Module
    {
        $existing = Module::query()->where('slug', 'pedidos')->first();

        if ($existing) {
            return $existing->load(['fields', 'kanbanStatuses']);
        }

        return $this->createPedidosModule();
    }

    private function createPedidosModule(): Module
    {
        $module = Module::create([
            'name' => 'Pedidos',
            'slug' => 'pedidos',
            'icon' => 'shopping-cart',
            'status' => ModuleStatus::ACTIVE->value,
        ]);

        return $module->load(['fields', 'kanbanStatuses']);
    }
}
