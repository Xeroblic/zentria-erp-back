<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_notification_defaults', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreignId('notification_type_id')->constrained('notification_types')->cascadeOnDelete();
            $table->boolean('allowed')->default(true);
            $table->json('channels')->nullable();
            $table->char('priority_override', 2)->nullable();
            $table->timestamps();

            $table->unique(['role_id','notification_type_id'], 'role_notif_defaults_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE role_notification_defaults ADD CONSTRAINT role_notif_defaults_priority_check CHECK (priority_override IS NULL OR priority_override IN ('P1','P2','P3'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_notification_defaults');
    }
};

