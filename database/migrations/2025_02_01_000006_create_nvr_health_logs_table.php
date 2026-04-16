<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nvr_health_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nvr_id')->constrained('nvrs')->cascadeOnDelete();
            $table->unsignedBigInteger('hdd_total_bytes')->nullable();
            $table->unsignedBigInteger('hdd_used_bytes')->nullable();
            $table->unsignedBigInteger('hdd_free_bytes')->nullable();
            $table->unsignedTinyInteger('hdd_usage_percent')->nullable(); // 0-100
            $table->string('hdd_status', 30)->nullable(); // ok, warning, critical, error
            $table->boolean('is_recording')->default(false);
            $table->unsignedInteger('recording_channels')->default(0);
            $table->unsignedInteger('bandwidth_kbps')->nullable();
            $table->unsignedSmallInteger('cpu_usage_percent')->nullable();
            $table->unsignedSmallInteger('memory_usage_percent')->nullable();
            $table->string('firmware_version', 50)->nullable();
            $table->unsignedSmallInteger('uptime_hours')->nullable();
            $table->enum('overall_status', ['healthy', 'warning', 'critical', 'unreachable'])->default('unreachable');
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['nvr_id', 'created_at']);
            $table->index('overall_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nvr_health_logs');
    }
};
