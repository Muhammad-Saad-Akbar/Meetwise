<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('mime_type')->default('video/webm');
            $table->string('status')->default('recording'); // recording | completed
            $table->unsignedInteger('chunk_count')->default(0);
            $table->unsignedBigInteger('size')->default(0);      // bytes
            $table->unsignedInteger('duration')->default(0);     // seconds
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
