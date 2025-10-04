<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_personalizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // FK correcta
            $table->unsignedInteger('tema')->nullable();
            $table->unsignedInteger('font_size')->nullable();

            $table->unsignedBigInteger('sucursal_principal')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sucursal_principal')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_personalizations');
    }
};
