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
         Schema::create('categories', function (Blueprint $table) {
            $table->id();                       // bigint PK
            $table->string('name', 255);        // varchar(255) NOT NULL

            // Jerarquía (padre opcional)
            $table->foreignId('parent_id')
              ->nullable()
              ->constrained('categories')   // FK a categories.id
              ->nullOnDelete();             // si borras el padre, deja NULL

            $table->string('slug', 250)->unique()->nullable(); 
            $table->boolean('is_active')->default(true);
            // Timestamps y soft delete con precisión 0 (timestamp(0))
            $table->timestamps();
            $table->softDeletes();

            // Índices útiles
            $table->index('parent_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
