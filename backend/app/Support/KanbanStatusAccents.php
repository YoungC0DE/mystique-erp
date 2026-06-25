<?php

namespace App\Support;

class KanbanStatusAccents
{
    /**
     * Cores flat por slug de status (legado + padrão atual).
     */
    public static function for(string $slug): string
    {
        return match ($slug) {
            'inputar', 'backlog' => '#94a3b8',
            'em_andamento', 'processando' => '#f59e0b',
            'aprovados', 'finalizado' => '#22c55e',
            'reprovados', 'reprovado' => '#ef4444',
            default => '#8b5cf6',
        };
    }
}
