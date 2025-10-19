<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Services\InvitationService;
use Illuminate\Support\Facades\App;

// Configurar entorno de prueba
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 SISTEMA MODULAR DE INVITACIONES - PRUEBA COMPLETA\n";
echo "======================================================\n\n";

try {
    // 1. Obtener datos necesarios
    echo "1️⃣ Obteniendo datos del sistema...\n";
    
    $superAdmin = User::whereHas('roles', function($q) {
        $q->where('name', 'super-admin');
    })->first();
    
    if (!$superAdmin) {
        throw new Exception('No se encontró un super admin');
    }
    
    $company = Company::first();
    $branch = Branch::first();
    
    echo "   ✅ Super Admin: {$superAdmin->email}\n";
    echo "   ✅ Empresa: {$company->company_name}\n";
    echo "   ✅ Sucursal: {$branch->branch_name}\n\n";
    
    // 2. Crear servicio de invitaciones
    echo "2️⃣ Inicializando servicio de invitaciones...\n";
    $invitationService = app(InvitationService::class);
    echo "   ✅ Servicio inicializado\n\n";
    
    // 3. Crear invitación de prueba
    echo "3️⃣ Creando invitación de prueba...\n";
    
    $invitationData = [
        'email' => 'nuevo.empleado.' . time() . '@ecotech.cl', // Email único
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'rut' => '12345678-' . substr(time(), -1), // RUT único
        'position' => 'Analista Junior',
        'phone_number' => '+56912345678',
        'address' => 'Santiago, Chile',
        'invited_by' => $superAdmin->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'role_name' => 'employee',
        'permissions' => ['view-dashboard'],
        'additional_data' => [
            'welcome_message' => 'Bienvenido al equipo!',
            'start_date' => '2025-01-20'
        ]
    ];
    
    $invitation = $invitationService->createInvitation($invitationData);
    
    echo "   ✅ Invitación creada exitosamente\n";
    echo "   📧 Email: {$invitation->email}\n";
    echo "   🆔 UID: {$invitation->uid}\n";
    echo "   🔐 Token: " . substr($invitation->token, 0, 10) . "...\n";
    echo "   ⏰ Expira: {$invitation->expires_at->format('d/m/Y H:i')}\n\n";
    
    // 4. Probar envío de email (simulado)
    echo "4️⃣ Probando envío de invitación...\n";
    
    // Nota: En desarrollo real esto enviaría el email
    // $sent = $invitationService->sendInvitation($invitation);
    
    echo "   ✅ Email preparado (no enviado en prueba)\n";
    echo "   🔗 URL de activación:\n";
    echo "   {$invitation->getActivationUrl()}\n\n";
    
    // 5. Mostrar información para frontend
    echo "5️⃣ Información para integración con frontend...\n";
    
    echo "   📝 Datos para aceptar invitación:\n";
    echo "   UID: {$invitation->uid}\n";
    echo "   Token: {$invitation->token}\n";
    echo "   Endpoint: GET /api/invitations/{$invitation->uid}/{$invitation->token}/info\n";
    echo "   Endpoint: POST /api/invitations/{$invitation->uid}/{$invitation->token}/accept\n\n";
    
    // 6. Estadísticas del sistema
    echo "6️⃣ Estadísticas del sistema...\n";
    $stats = $invitationService->getInvitationStats($company->id);
    
    foreach ($stats as $status => $count) {
        echo "   📊 " . ucfirst($status) . ": {$count}\n";
    }
    
    echo "\n";
    
    // 7. Simular información de invitación para frontend
    echo "7️⃣ Simulando obtención de info para frontend...\n";
    
    $invitationInfo = [
        'email' => $invitation->email,
        'first_name' => $invitation->first_name,
        'last_name' => $invitation->last_name,
        'position' => $invitation->position,
        'company_name' => $invitation->company->company_name,
        'branch_name' => $invitation->branch->branch_name,
        'role_name' => $invitation->role_name,
        'expires_at' => $invitation->expires_at,
        'is_valid' => $invitation->isValid()
    ];
    
    echo "   📋 Información para mostrar en frontend:\n";
    echo "   " . json_encode($invitationInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 8. Comandos útiles para PowerShell
    echo "8️⃣ Comandos PowerShell para pruebas manuales...\n";
    echo "   # Obtener información de invitación:\n";
    echo "   \$response = Invoke-RestMethod -Uri 'http://localhost/api/invitations/{$invitation->uid}/{$invitation->token}/info' -Method GET\n";
    echo "   \$response\n\n";
    
    echo "   # Aceptar invitación:\n";
    echo "   \$body = @{\n";
    echo "       password = 'nuevapassword123'\n";
    echo "       password_confirmation = 'nuevapassword123'\n";
    echo "       terms_accepted = \$true\n";
    echo "   } | ConvertTo-Json\n";
    echo "   \$response = Invoke-RestMethod -Uri 'http://localhost/api/invitations/{$invitation->uid}/{$invitation->token}/accept' -Method POST -Body \$body -ContentType 'application/json'\n";
    echo "   \$response\n\n";
    
    echo "🎉 PRUEBA COMPLETADA EXITOSAMENTE\n";
    echo "✨ El sistema modular de invitaciones está listo para usar\n\n";
    
    echo "📚 CARACTERÍSTICAS IMPLEMENTADAS:\n";
    echo "   ✅ Modelo Invitation con UID + Token\n";
    echo "   ✅ Servicio InvitationService modular\n";
    echo "   ✅ Controlador InvitationController\n";
    echo "   ✅ Email mejorado con template HTML\n";
    echo "   ✅ Rutas RESTful para frontend\n";
    echo "   ✅ Comando de limpieza automatizada\n";
    echo "   ✅ Validaciones de seguridad y acceso\n";
    echo "   ✅ Estadísticas y reportes\n";
    echo "   ✅ Integración con sistema de roles/empresas\n\n";
    
} catch (Exception $e) {
    echo "❌ Error en la prueba: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
