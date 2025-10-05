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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            
            // Identificadores únicos
            $table->string('uid')->unique(); // UUID público
            $table->string('token', 64); // Token privado adicional
            
            // Datos del invitado
            $table->string('email')->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('rut')->nullable();
            $table->string('position')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            
            // Información de quien invita
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            
            // Asignación organizacional
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            
            // Roles y permisos a asignar
            $table->string('role_name'); // Rol principal
            $table->json('permissions')->nullable(); // Permisos adicionales
            
            // Credenciales temporales
            $table->string('temporary_password');
            
            // Estado y control
            $table->enum('status', ['pending', 'sent', 'accepted', 'expired', 'cancelled'])
                  ->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            
            // Datos adicionales (extensible)
            $table->json('data')->nullable();
            
            $table->timestamps();
            
            // Índices para optimización
            $table->index(['email', 'status']);
            $table->index(['uid', 'token']); 
            $table->index(['status', 'expires_at']);
            $table->index(['branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
