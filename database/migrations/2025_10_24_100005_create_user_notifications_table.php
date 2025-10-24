<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('notification_events')->cascadeOnDelete();
            $table->string('status', 20)->default('unread');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->json('delivered_channels')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('ack_at')->nullable();
            $table->integer('aggregate_count')->default(1);
            $table->timestamp('last_occurred_at')->nullable();
            $table->timestamps();

            $table->index(['user_id','status'], 'user_notif_user_status_idx');
            $table->index(['event_id'], 'user_notif_event_idx');
            $table->index(['read_at'], 'user_notif_read_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};

