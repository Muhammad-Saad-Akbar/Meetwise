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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
            $table->string('meeting_code')->unique();
            $table->enum('type', ['instant', 'scheduled']);
            $table->dateTime('scheduled_at')->nullable();
            $table->enum('status', ['upcoming', 'live', 'ended'])->default('upcoming');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
