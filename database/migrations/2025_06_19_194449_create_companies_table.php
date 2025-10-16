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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_rut')->unique();
            $table->string('company_website')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('representative_name')->nullable();
            $table->string('contact_email')->unique();
            $table->string('company_address')->nullable();
            $table->unsignedInteger('commune_id')->nullable();
            $table->string('business_activity')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('company_logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('company_type')->nullable();

            $table->foreign('commune_id')->references('id')->on('communes')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

