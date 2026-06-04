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
         Schema::create('detail_fields', function (Blueprint $table) {
             $table->id();
             $table->foreignId('field_id')->constrained('fields')->cascadeOnDelete();
             $table->text('description');
             $table->enum('surface_type', ['vinyl', 'parket', 'semen'])->default('vinyl');
             $table->decimal('rating', 3, 1)->default(0.0);
             $table->enum('status', ['available', 'maintenance'])->default('available');
             $table->timestamps();
         });
     }

     /**
      * Reverse the migrations.
      */
     public function down(): void
     {
         Schema::dropIfExists('detail_fields');
     }
};
