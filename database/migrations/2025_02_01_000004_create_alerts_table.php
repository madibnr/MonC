<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // camera_offline, nvr_disconnected, hdd_critical, recording_failed, stream_error
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title');
            $table->text('message');
            $table->string('source_type', 50)->nullable(); // camera, nvr, system
            $table->unsignedBigInteger('source_id')->nullable(); // camera_id or nvr_id
            $table->boolean('is_read')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index(['severity', 'is_resolved']);
            $table->index(['source_type', 'source_id']);
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
