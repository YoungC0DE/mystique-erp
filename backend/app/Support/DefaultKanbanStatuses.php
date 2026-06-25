<?php

namespace App\Support;

class DefaultKanbanStatuses
{
    /**
     * Status padrão do Kanban (Inputar · Em Andamento · Aprovados · Reprovados).
     *
     * @return list<array{slug: string, label: string, order: int, external_value: string}>
     */
    public static function definitions(): array
    {
        return [
            ['slug' => 'inputar', 'label' => 'Inputar', 'order' => 0, 'external_value' => 'Inputar'],
            ['slug' => 'em_andamento', 'label' => 'Em Andamento', 'order' => 1, 'external_value' => 'Em Andamento'],
            ['slug' => 'aprovados', 'label' => 'Aprovados', 'order' => 2, 'external_value' => 'Aprovados'],
            ['slug' => 'reprovados', 'label' => 'Reprovados', 'order' => 3, 'external_value' => 'Reprovados'],
        ];
    }
}
