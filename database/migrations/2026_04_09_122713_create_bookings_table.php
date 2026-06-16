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
            $table->id();
            $table->string('booking_number', 50)->unique()->index();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'expired'])->default('pending');
            $table->enum('booking_type', ['paid', 'requirement']);
            $table->integer('total_price')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->string('file_url')->nullable();
            $table->string('qr_id')->nullable();
            $table->string('qr_string')->nullable();
            $table->boolean("is_attended")->default(false)->nullable();
            $table->timestamp("attended_at")->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_bookings_user_id');
            $table->index('status', 'idx_bookings_status');
            $table->index('expires_at', 'idx_bookings_expires_at');
            $table->index('booking_type', 'idx_bookings_booking_type');
            $table->index('created_at', 'idx_bookings_created_at');
            $table->index(['status', 'expires_at'], 'idx_bookings_status_expires_at');
            $table->index(['user_id', 'status'], 'idx_bookings_user_id_status');
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
