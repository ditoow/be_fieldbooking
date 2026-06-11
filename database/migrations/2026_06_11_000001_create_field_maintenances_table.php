<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('field_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained('fields')->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time')->nullable();  // null = seharian
            $table->time('end_time')->nullable();    // null = seharian
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['field_id', 'date', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_maintenances');
    }
};
