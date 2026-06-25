<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_records', function (Blueprint $table) {
            $table->index(['module_id', 'status', 'created_at'], 'module_records_module_status_created_idx');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->index(['status', 'name'], 'modules_status_name_idx');
            $table->index('created_at', 'modules_created_at_idx');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->index('name', 'permissions_name_idx');
        });

        Schema::table('database_connections', function (Blueprint $table) {
            $table->index('name', 'database_connections_name_idx');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->index('created_at', 'roles_created_at_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at', 'users_created_at_idx');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->index('created_at', 'reports_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('module_records', function (Blueprint $table) {
            $table->dropIndex('module_records_module_status_created_idx');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropIndex('modules_status_name_idx');
            $table->dropIndex('modules_created_at_idx');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex('permissions_name_idx');
        });

        Schema::table('database_connections', function (Blueprint $table) {
            $table->dropIndex('database_connections_name_idx');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex('roles_created_at_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_created_at_idx');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex('reports_created_at_idx');
        });
    }
};
