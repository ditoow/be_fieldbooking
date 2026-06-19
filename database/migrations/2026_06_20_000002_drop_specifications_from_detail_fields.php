<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_fields', function (Blueprint $table) {
            $table->dropColumn('specifications');
        });
    }

    public function down(): void
    {
        Schema::table('detail_fields', function (Blueprint $table) {
            $table->json('specifications')->nullable();
        });
    }
};
