<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $legacyMap = [
        'backlog' => 'inputar',
        'processando' => 'em_andamento',
        'finalizado' => 'aprovados',
        'reprovado' => 'reprovados',
    ];

    public function up(): void
    {
        foreach ($this->legacyMap as $old => $new) {
            DB::table('module_records')->where('status', $old)->update(['status' => $new]);
        }

        Module::query()->each(function (Module $module) {
            $module->ensureDefaultKanbanStatuses();
        });
    }

    public function down(): void
    {
        $reverse = array_flip($this->legacyMap);

        foreach ($reverse as $new => $old) {
            DB::table('module_records')->where('status', $new)->update(['status' => $old]);
        }
    }
};
