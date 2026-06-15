<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('model');
            $table->string('collection_name');
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type');
            $table->unsignedInteger('file_size');
            $table->string('bucket');
            $table->string('url');
            $table->json('custom_properties')->nullable();
            $table->timestamps();

            $table->index('collection_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
