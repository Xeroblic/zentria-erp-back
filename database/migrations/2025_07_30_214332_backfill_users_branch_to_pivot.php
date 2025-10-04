<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Si no hay branch_user o no existe branch_id legacy, no hacemos nada
        if (!Schema::hasTable('branch_user')) {
            return;
        }
        if (!Schema::hasColumn('users', 'branch_id')) {
            return;
        }

        // 1) Volcar users.branch_id -> branch_user (is_primary = true)
        DB::table('users')
            ->whereNotNull('branch_id')
            ->orderBy('id')
            ->chunkById(1000, function ($users) {
                $now = now();
                $rows = [];

                foreach ($users as $u) {
                    $rows[] = [
                        'user_id'    => $u->id,
                        'branch_id'  => $u->branch_id,
                        'is_primary' => true,
                        // Si no tienes columna position en users, deja null
                        'position'   => $u->position ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($rows)) {
                    // Evita duplicados si corres la migración más de una vez
                    DB::table('branch_user')->upsert(
                        $rows,
                        ['user_id', 'branch_id'],
                        ['is_primary', 'position', 'updated_at']
                    );
                }
            });

        // 2) Copiar a users.primary_branch_id si existe esa columna
        if (Schema::hasColumn('users', 'primary_branch_id')) {
            DB::statement(
                'UPDATE users 
                 SET primary_branch_id = branch_id 
                 WHERE branch_id IS NOT NULL 
                   AND (primary_branch_id IS NULL OR primary_branch_id <> branch_id)'
            );
        }

        // OJO: NO cambiar schema aquí. Deja drop/nullable para otra migración.
    }

    public function down(): void
    {
        // opcional: limpiar lo agregado, normalmente no se revierte data de backfill
    }
};
