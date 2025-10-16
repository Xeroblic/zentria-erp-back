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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('second_last_name')->nullable();
            $table->string('position')->nullable();
            $table->string('rut')->unique();
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->unsignedInteger('commune_id')->nullable();
            $table->string('email')->unique();
            $table->date('date_of_birth')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('primary_branch_id')->nullable();
            $table->string('gender')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            $table->foreign('commune_id')->references('id')->on('communes')->onDelete('set null');
            $table->foreign('primary_branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
