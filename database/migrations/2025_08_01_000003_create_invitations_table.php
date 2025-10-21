<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         |---------------------------------------------
         | Invitations table (branch-scoped, lean)
         |---------------------------------------------
         */
        Schema::create('invitations', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('uid', 255);                 // ULID string for human-friendly tracking
            $table->string('token', 64)->unique();      // UUID v4 fits in 36, 64 gives headroom

            $table->string('email', 255);
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->string('rut', 255)->nullable();
            $table->string('position', 255)->nullable();
            $table->string('phone_number', 255)->nullable();
            $table->text('address')->nullable();

            // Scope to branch; company/subsidiary derived via relations
            $table->foreignId('invited_by')->constrained('users');    // ->cascadeOnDelete() if desired
            $table->foreignId('branch_id')->constrained('branches');  // ->cascadeOnDelete() if desired
            $table->foreignId('role_id')->nullable()->constrained('roles');

            $table->string('role_name', 255);           // e.g. company-admin | subsidiary-admin | branch-admin
            $table->json('permissions')->nullable();    // optional granular overrides

            $table->string('temporary_password', 255);  // store HASH here (never plain text)

            $table->string('status', 255)->default('pending'); // pending | used | expired
            $table->timestamp('expires_at');             // NOT NULL (set by service)
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();

            $table->json('data')->nullable();           // any extra payload/snapshot
            $table->timestamps();                       // created_at / updated_at

            $table->index(['email', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['role_id']);
        });

        /*
         |---------------------------------------------
         | Users table tweaks: activo + token_activacion
         |---------------------------------------------
         */
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(false)->after('remember_token');
            $table->uuid('token_activacion')->nullable()->after('activo');

        });
    }

    public function down(): void
    {
        // Revert users first
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'token_activacion')) {
                $table->dropColumn('token_activacion');
            }
            if (Schema::hasColumn('users', 'activo')) {
                $table->dropColumn('activo');
            }

            if (Schema::hasColumn('users', 'email')) {
                $table->dropUnique('users_email_unique');
            }
        });

        Schema::dropIfExists('invitations');
    }
};
