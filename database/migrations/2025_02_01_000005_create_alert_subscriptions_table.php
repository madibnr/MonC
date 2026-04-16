<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('alert_type', 50); // camera_offline, nvr_disconnected, hdd_critical, recording_failed, all
            $table->string('channel', 20); // web, email, telegram
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'alert_type', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_subscriptions');
    }
};
