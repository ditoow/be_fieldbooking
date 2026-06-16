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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained('fields');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('price')->default(0);
            $table->timestamps();

            $table->unique(['field_id', 'date', 'start_time']);
            $table->index(['field_id', 'date'], 'idx_schedules_field_id_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
