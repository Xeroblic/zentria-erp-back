<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'primary_branch_id')) {
                $table->unsignedBigInteger('primary_branch_id')->nullable()->after('is_active');
                $table->foreign('primary_branch_id')
                      ->references('id')->on('branches')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'primary_branch_id')) {
                $table->dropForeign(['primary_branch_id']);
                $table->dropColumn('primary_branch_id');
            }
        });
    }
};
