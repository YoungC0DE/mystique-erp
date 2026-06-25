<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('module_records')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('module_fields')->cascadeOnDelete();
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['record_id', 'field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_values');
    }
};
