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
            $table->id('id')->primary();
            $table->foreignId('lapangan_id')->constrained('fields');
            $table->date('hari');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->integer('harga')->default(0);
            $table->enum('status', ['available', 'booked'])->default('available');
            $table->timestamps();

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
