<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('notification_types')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('subsidiary_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->char('priority', 2);
            $table->json('payload')->nullable();
            $table->string('dedup_key');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['dedup_key','occurred_at'], 'notif_events_dedup_idx');
            $table->index(['occurred_at'], 'notif_events_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_events');
    }
};

