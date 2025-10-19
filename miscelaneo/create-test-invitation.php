<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Services\InvitationService;

// Configurar entorno
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 CREANDO INVITACIÓN RÁPIDA PARA PRUEBAS\n";
echo "========================================\n\n";

try {
    $service = app(InvitationService::class);
    $superAdmin = User::whereHas('roles', function($q) { 
        $q->where('name', 'super-admin'); 
    })->first();
    $company = Company::first();
    $branch = Branch::first();

    $invitation = $service->createInvitation([
        'email' => 'test.quick.' . time() . '@ecotech.cl',
        'first_name' => 'Test',
        'last_name' => 'User',
        'invited_by' => $superAdmin->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'role_name' => 'employee'
    ]);

    echo "✅ Invitación creada exitosamente:\n";
    echo "   📧 Email: {$invitation->email}\n";
    echo "   🆔 UID: {$invitation->uid}\n";
    echo "   🔐 Token: {$invitation->token}\n\n";
    
    echo "🔗 URLs para probar:\n";
    echo "   INFO: http://chilopson-erp-back.test/api/invitations/{$invitation->uid}/{$invitation->token}/info\n";
    echo "   ACCEPT: http://chilopson-erp-back.test/api/invitations/{$invitation->uid}/{$invitation->token}/accept\n\n";
    
    echo "📝 Comando PowerShell para probar:\n";
    echo "   \$info = Invoke-RestMethod -Uri 'http://chilopson-erp-back.test/api/invitations/{$invitation->uid}/{$invitation->token}/info'\n";
    echo "   \$info.data\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
