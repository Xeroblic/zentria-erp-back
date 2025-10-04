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
        Schema::create('user_company_personalizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // Preferencias específicas por empresa
            $table->string('tema')->default('light');
            $table->integer('font_size')->default(14);
            $table->foreignId('preferred_subsidiary_id')->nullable()->constrained('subsidiaries')->nullOnDelete();
            $table->foreignId('preferred_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            
            // Dashboard personalizado por empresa
            $table->json('dashboard_widgets')->nullable();
            $table->boolean('sidebar_collapsed')->default(false);
            $table->string('language')->default('es');
            
            $table->timestamps();
            
            $table->unique(['user_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_company_personalizations');
    }
};
