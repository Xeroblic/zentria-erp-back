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
        Schema::create('provinces', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name', 64);
            $table->unsignedInteger('region_id');
            $table->foreign('region_id')->references('id')->on('regions')->cascadeOnUpdate()->restrictOnDelete();
            $table->index('region_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
