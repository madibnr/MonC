<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type', 50); // login, logout, live_view, playback, export, snapshot, permission_change, settings_change, camera_manage, nvr_manage, etc.
            $table->string('module', 50); // auth, live, playback, export, snapshot, camera, nvr, building, user, permission, settings, alert, system
            $table->foreignId('camera_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable(); // extra context data
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['action_type', 'created_at']);
            $table->index(['module', 'created_at']);
            $table->index(['camera_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
