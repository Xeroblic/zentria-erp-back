<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_notification_id')->constrained('user_notifications')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->timestamp('delivered_at');
            $table->string('status', 50)->default('ok');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_notification_id','channel'], 'notif_delivery_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_logs');
    }
};

