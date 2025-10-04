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
        Schema::create('subsidiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('subsidiary_name');
            $table->string('subsidiary_rut')->unique();
            $table->string('subsidiary_website')->nullable();
            $table->string('subsidiary_phone')->nullable();
            $table->string('subsidiary_address')->nullable();
            $table->string('subsidiary_email')->unique();
            $table->timestamp('subsidiary_created_at')->nullable();
            $table->timestamp('subsidiary_updated_at')->nullable();
            $table->string('subsidiary_manager_name');
            $table->string('subsidiary_manager_phone')->nullable();
            $table->string('subsidiary_manager_email')->unique();
            $table->boolean('subsidiary_status')->default(true);
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsidiaries');
    }
};
