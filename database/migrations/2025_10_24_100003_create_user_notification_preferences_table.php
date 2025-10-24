<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_type_id')->constrained('notification_types')->cascadeOnDelete();
            $table->boolean('allowed')->default(true);
            $table->json('channels')->nullable();
            $table->timestamp('snooze_until')->nullable();
            $table->json('quiet_hours')->nullable();
            $table->timestamps();

            $table->unique(['user_id','notification_type_id'], 'user_notif_prefs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};

