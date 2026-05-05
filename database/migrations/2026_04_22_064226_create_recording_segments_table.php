<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recording_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->string('file_path');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->enum('status', ['recording', 'completed', 'failed', 'orphaned'])->default('recording');
            $table->enum('type', ['continuous', 'motion', 'event'])->default('continuous');
            $table->timestamps();

            // Fast lookup: camera + time range queries
            $table->index(['camera_id', 'start_time', 'end_time']);
            $table->index(['camera_id', 'status']);
            $table->index('start_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recording_segments');
    }
};
