<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('module')->nullable();
            $table->text('description')->nullable();
            $table->char('default_priority', 2);
            $table->json('default_channels')->nullable();
            $table->boolean('critical')->default(false);
            $table->boolean('enabled_global')->default(true);
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE notification_types ADD CONSTRAINT notification_types_priority_check CHECK (default_priority IN ('P1','P2','P3'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_types');
    }
};

