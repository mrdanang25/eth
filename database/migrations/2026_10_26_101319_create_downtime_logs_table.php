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
        Schema::create('downtime_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('monitored_devices')->onDelete('cascade');
            $table->timestamp('went_offline_at');
            $table->timestamp('came_online_at')->nullable();
            $table->integer('total_duration_minutes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downtime_logs');
    }
};
