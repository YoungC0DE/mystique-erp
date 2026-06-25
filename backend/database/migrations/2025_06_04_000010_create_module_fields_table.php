<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('label');
            $table->string('key');
            $table->string('type');
            $table->boolean('required')->default(false);
            $table->text('default_value')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('show_in_card')->default(false);
            $table->boolean('show_in_list')->default(true);
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->unique(['module_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_fields');
    }
};
