<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->foreignId('connection_id')
                ->nullable()
                ->after('status')
                ->constrained('database_connections')
                ->nullOnDelete();
            $table->string('callback_url')->nullable()->after('connection_id');
            $table->string('callback_method')->default('POST')->after('callback_url');
            $table->string('status_column')->nullable()->after('callback_method');
        });

        Schema::create('module_kanban_statuses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('slug');
            $table->string('label');
            $table->unsignedInteger('order')->default(0);
            $table->string('external_value');
            $table->timestamps();

            $table->unique(['module_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_kanban_statuses');

        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['connection_id']);
            $table->dropColumn(['connection_id', 'callback_url', 'callback_method', 'status_column']);
        });
    }
};
