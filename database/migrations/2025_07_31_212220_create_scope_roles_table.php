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
    Schema::create('scope_roles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
        $table->string('scope_type'); // 'company', 'subsidiary', 'branch'
        $table->unsignedBigInteger('scope_id');
        $table->timestamps();

        $table->index(['user_id', 'role_id', 'scope_type', 'scope_id'], 'context_role_idx');
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scope_roles');
    }
};
