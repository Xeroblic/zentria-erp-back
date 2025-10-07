<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255)->unique()->nullable();
            $table->boolean('is_active')->default(true);

            // timestamps(0) y soft deletes en PG
            $table->timestamps();
            $table->softDeletes(); // ✅ sin argumentos

            // Índice auxiliar (no único) para consultas por branch+name
            $table->index(['branch_id', 'name']);
        });

        // Unicidad case-insensitive por sucursal: (branch_id, lower(name))
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS brands_branch_lower_name_uniq ON brands (branch_id, lower(name));');
    }

    public function down(): void
    {
        // Borra el índice funcional antes de eliminar la tabla
        DB::statement('DROP INDEX IF EXISTS brands_branch_lower_name_uniq');
        Schema::dropIfExists('brands');
    }
};
