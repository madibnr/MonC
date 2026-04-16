<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_camera_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained('cameras')->cascadeOnDelete();
            $table->boolean('ai_enabled')->default(false);
            $table->string('ai_type', 50)->default('plate_recognition');
            $table->unsignedInteger('detection_interval_seconds')->default(5);
            $table->unsignedTinyInteger('confidence_threshold')->default(85);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('camera_id');
            $table->index(['ai_enabled', 'ai_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_camera_settings');
    }
};
