<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('user_id', 'idx_bookings_user_id');
            $table->index('status', 'idx_bookings_status');
            $table->index('expires_at', 'idx_bookings_expires_at');
            $table->index('booking_type', 'idx_bookings_booking_type');
            $table->index('created_at', 'idx_bookings_created_at');
            $table->index(['status', 'expires_at'], 'idx_bookings_status_expires_at');
            $table->index(['user_id', 'status'], 'idx_bookings_user_id_status');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['field_id', 'date'], 'idx_schedules_field_id_date');
        });

        Schema::table('fields', function (Blueprint $table) {
            $table->index('category', 'idx_fields_category');
            $table->softDeletes();
        });

        Schema::table('detail_fields', function (Blueprint $table) {
            $table->index(['field_id', 'status'], 'idx_detail_fields_field_id_status');
        });

        Schema::table('field_maintenances', function (Blueprint $table) {
            $table->index(['field_id', 'date'], 'idx_field_maintenances_field_id_date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('status', 'idx_users_status');
            $table->index('created_at', 'idx_users_created_at');
        });

        Schema::table('booking_details', function (Blueprint $table) {
            $table->index(['booking_id', 'schedule_id'], 'idx_booking_details_booking_id_schedule_id');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at', 'idx_activity_logs_created_at');
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_user_id');
            $table->dropIndex('idx_bookings_status');
            $table->dropIndex('idx_bookings_expires_at');
            $table->dropIndex('idx_bookings_booking_type');
            $table->dropIndex('idx_bookings_created_at');
            $table->dropIndex('idx_bookings_status_expires_at');
            $table->dropIndex('idx_bookings_user_id_status');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_field_id_date');
        });

        Schema::table('fields', function (Blueprint $table) {
            $table->dropIndex('idx_fields_category');
            $table->dropSoftDeletes();
        });

        Schema::table('detail_fields', function (Blueprint $table) {
            $table->dropIndex('idx_detail_fields_field_id_status');
        });

        Schema::table('field_maintenances', function (Blueprint $table) {
            $table->dropIndex('idx_field_maintenances_field_id_date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_status');
            $table->dropIndex('idx_users_created_at');
        });

        Schema::table('booking_details', function (Blueprint $table) {
            $table->dropIndex('idx_booking_details_booking_id_schedule_id');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_activity_logs_created_at');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
