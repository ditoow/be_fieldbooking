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
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignId('schedule_id')->constrained('schedules');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('berakhir_pada');
            //mahasiswa
            $table->string('file_url')->nullable();
            $table->boolean("is_hadir")->default(false)->nullable();
            $table->timestamp("hadir_pada")->nullable();

            $table->timestamps();
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
