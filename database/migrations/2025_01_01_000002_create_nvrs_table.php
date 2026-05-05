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
        Schema::create('nvrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->string('name');
            $table->string('ip_address');
            $table->integer('port')->default(554);
            $table->text('username');
            $table->text('password');
            $table->string('model')->nullable();
            $table->integer('total_channels')->default(64);
            $table->enum('status', ['online', 'offline', 'maintenance'])->default('offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nvrs');
    }
};
