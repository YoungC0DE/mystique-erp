<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_fields', function (Blueprint $table) {
            $table->boolean('show_in_detail')->default(true)->after('show_in_list');
            $table->boolean('highlighted')->default(false)->after('show_in_detail');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->json('detail_layout')->nullable()->after('status_column');
        });
    }

    public function down(): void
    {
        Schema::table('module_fields', function (Blueprint $table) {
            $table->dropColumn(['show_in_detail', 'highlighted']);
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('detail_layout');
        });
    }
};
