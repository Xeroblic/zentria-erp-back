<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Crear roles de acceso (no administrativos)
        Role::findOrCreate('company-member', 'api');
        Role::findOrCreate('subsidiary-member', 'api');
    }

    public function down(): void
    {
        // No eliminar para no romper historiales, pero podrías optar por borrarlos
    }
};

