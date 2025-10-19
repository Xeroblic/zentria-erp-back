<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scope_roles')) {
            Schema::table('scope_roles', function (Blueprint $table) {
                // Unique para evitar duplicados exactos de contexto
                $table->unique(['user_id','role_id','scope_type','scope_id'], 'scope_roles_user_role_scope_unique');
                // Búsquedas comunes
                $table->index(['user_id','scope_type'], 'scope_roles_user_type_idx');
                $table->index(['scope_type','scope_id'], 'scope_roles_type_id_idx');
            });
        }

        if (Schema::hasTable('branch_user')) {
            Schema::table('branch_user', function (Blueprint $table) {
                // Listar usuarios por branch de forma eficiente
                $table->index('branch_id', 'branch_user_branch_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('scope_roles')) {
            Schema::table('scope_roles', function (Blueprint $table) {
                $table->dropUnique('scope_roles_user_role_scope_unique');
                $table->dropIndex('scope_roles_user_type_idx');
                $table->dropIndex('scope_roles_type_id_idx');
            });
        }

        if (Schema::hasTable('branch_user')) {
            Schema::table('branch_user', function (Blueprint $table) {
                $table->dropIndex('branch_user_branch_idx');
            });
        }
    }
};

