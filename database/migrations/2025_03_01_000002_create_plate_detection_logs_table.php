<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plate_detection_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained('cameras')->cascadeOnDelete();
            $table->string('plate_number', 20);
            $table->string('plate_number_normalized', 20)->index();
            $table->decimal('confidence', 5, 2);
            $table->string('snapshot_path')->nullable();
            $table->string('vehicle_type', 50)->nullable();
            $table->string('vehicle_color', 30)->nullable();
            $table->enum('direction', ['in', 'out', 'unknown'])->default('unknown');
            $table->json('bounding_box')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();

            $table->index(['camera_id', 'detected_at']);
            $table->index(['plate_number_normalized', 'detected_at']);
            $table->index('detected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plate_detection_logs');
    }
};
