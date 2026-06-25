<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, string> Material-style names → Lucide kebab-case */
    private const ICON_MAP = [
        'shopping_cart' => 'shopping-cart',
        'inventory_2' => 'package',
        'view_kanban' => 'kanban',
        'view_module' => 'layout-grid',
        'dashboard' => 'layout-dashboard',
        'group' => 'users',
        'groups' => 'users',
        'bar_chart' => 'bar-chart',
        'light_mode' => 'sun',
        'dark_mode' => 'moon',
        'filter_list' => 'list-filter',
        'drag_indicator' => 'grip-vertical',
        'keyboard_arrow_up' => 'chevron-up',
        'keyboard_arrow_down' => 'chevron-down',
        'arrow_back' => 'arrow-left',
        'add' => 'plus',
        'delete' => 'trash-2',
        'close' => 'x',
        'chevron_left' => 'chevron-left',
        'chevron_right' => 'chevron-right',
        'storage' => 'database',
        'extension' => 'puzzle',
        'sync_alt' => 'refresh-cw',
    ];

    public function up(): void
    {
        foreach (self::ICON_MAP as $legacy => $lucide) {
            DB::table('modules')
                ->where('icon', $legacy)
                ->update(['icon' => $lucide]);
        }
    }

    public function down(): void
    {
        foreach (self::ICON_MAP as $legacy => $lucide) {
            DB::table('modules')
                ->where('icon', $lucide)
                ->update(['icon' => $legacy]);
        }
    }
};
