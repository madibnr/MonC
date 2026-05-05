<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watchlist_plates', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 20);
            $table->string('plate_number_normalized', 20)->index();
            $table->enum('alert_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('reason')->nullable();
            $table->string('vehicle_owner')->nullable();
            $table->string('vehicle_description')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('notify_telegram')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('plate_number_normalized');
            $table->index(['is_active', 'plate_number_normalized']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlist_plates');
    }
};
