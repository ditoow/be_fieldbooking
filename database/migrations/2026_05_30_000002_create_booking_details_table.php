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
        Schema::disableForeignKeyConstraints();

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->dropColumn('schedule_id');
            $table->integer('total_price')->default(0)->after('booking_type');
            $table->timestamp('expires_at')->nullable()->change();
        });

        Schema::create('booking_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('schedules');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('booking_details');

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->cascadeOnDelete();
            $table->dropColumn('total_price');
            $table->timestamp('expires_at')->nullable(false)->change();
        });

        Schema::enableForeignKeyConstraints();
    }
};
