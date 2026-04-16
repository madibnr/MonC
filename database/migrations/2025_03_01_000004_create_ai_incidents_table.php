<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained('cameras')->cascadeOnDelete();
            $table->foreignId('plate_detection_log_id')->nullable()->constrained('plate_detection_logs')->nullOnDelete();
            $table->foreignId('watchlist_plate_id')->nullable()->constrained('watchlist_plates')->nullOnDelete();
            $table->string('incident_type', 50);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('plate_number', 20)->nullable();
            $table->string('snapshot_path')->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['incident_type', 'occurred_at']);
            $table->index(['severity', 'is_acknowledged']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_incidents');
    }
};
