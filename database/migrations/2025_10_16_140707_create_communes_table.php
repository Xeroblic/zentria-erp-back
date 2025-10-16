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
        Schema::create('communes', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name', 64);
            $table->unsignedInteger('province_id');
            $table->foreign('province_id')->references('id')->on('provinces')->cascadeOnUpdate()->restrictOnDelete();
            $table->index('province_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communes');
    }
};
