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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subsidiary_id');
            $table->string('branch_name');
            $table->string('branch_address');
            $table->string('branch_phone');
            $table->string('branch_email');
            $table->timestamp('branch_created_at')->nullable();
            $table->timestamp('branch_updated_at')->nullable();
            $table->boolean('branch_status')->default(true);
            $table->string('branch_manager_name');
            $table->string('branch_manager_phone')->nullable();
            $table->string('branch_manager_email');
            $table->string('branch_opening_hours')->nullable();
            $table->string('branch_location')->nullable();
            $table->timestamps();
            
            $table->foreign('subsidiary_id')->references('id')->on('subsidiaries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
