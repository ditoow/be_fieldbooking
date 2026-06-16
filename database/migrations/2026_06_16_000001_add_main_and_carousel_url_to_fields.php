<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->json('carousel_urls')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->dropColumn('carousel_urls');
        });
    }
};
