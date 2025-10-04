<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreatePermissionGroupsConfig extends Command
{
    protected $signature = 'make:create-permission-groups-config';

    protected $description = 'Crear el archivo config/permission_groups.php con modulos y acciones';

    public function handle(): int
    {
        
        $path = config_path('permission_groups.php');

        if (File::exists($path)) {
            $this->warn("El archivo config/permission_groups.php ya existe.");
            return 0;
        }
        $content = <<<PHP
<?php

return [

    'dashboard' => [
        'view-dashboard',
    ],

    'branch' => [
        'view-branch',
        'create-branch',
        'edit-branch',
        'delete-branch',
    ],

    'subsidiary' => [
        'view-subsidiary',
        'create-subsidiary',
        'edit-subsidiary',
        'delete-subsidiary',
    ],

    'company' => [
        'view-company',
        'edit-company',
    ],

    'user' => [
        'view-user',
        'create-user',
        'edit-user',
        'delete-user',
        'invite-user',
    ],

    'role' => [
        'view-role',
        'assign-role',
    ],
];
PHP;

        File::put($path, $content);

        $this->info("Archivo config/permission_groups.php creado exitosamente.");
        return Command::SUCCESS;
    }
}
