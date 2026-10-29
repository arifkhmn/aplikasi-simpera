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
        Schema::create('reminder_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('interval_days'); // 30, 7, 1 hari sebelum jatuh tempo
            $table->string('channel', 30)->default('system'); // 'system', 'email', 'app'
            $table->timestamp('sent_at');
            $table->string('status', 20)->default('sent'); // 'sent', 'failed'
            $table->timestamps();

            // Mencegah pengiriman berulang untuk pengingat, interval hari, dan kanal yang sama
            $table->unique(['reminder_id', 'interval_days', 'channel'], 'reminder_notif_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_notifications');
    }
};
