<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvitationService;

class CleanupExpiredInvitations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'invitations:cleanup 
                           {--dry-run : Solo mostrar qué se limpiaría sin hacer cambios}
                           {--days=7 : Limpiar invitaciones expiradas hace X días}';

    /**
     * The console command description.
     */
    protected $description = 'Limpiar invitaciones expiradas del sistema';

    protected $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        parent::__construct();
        $this->invitationService = $invitationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        
        $this->info("🔍 Iniciando limpieza de invitaciones expiradas...");
        
        if ($dryRun) {
            $this->warn("⚠️  MODO DRY-RUN: No se realizarán cambios reales");
        }
        
        $this->info("📅 Buscando invitaciones expiradas hace más de {$days} días");
        
        try {
            if ($dryRun) {
                $count = $this->previewCleanup($days);
                $this->table(
                    ['Acción', 'Cantidad'],
                    [
                        ['Invitaciones a marcar como expiradas', $count],
                        ['Cambios reales', 'NINGUNO (dry-run)']
                    ]
                );
            } else {
                $count = $this->invitationService->cleanupExpiredInvitations();
                $this->info("✅ Se marcaron {$count} invitaciones como expiradas");
                
                if ($count > 0) {
                    $this->call('cache:clear');
                    $this->info("🧹 Cache limpiado");
                }
            }
            
            // Mostrar estadísticas actuales
            $this->showCurrentStats();
            
            $this->info("🎉 Limpieza completada exitosamente");
            
        } catch (\Exception $e) {
            $this->error("❌ Error durante la limpieza: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function previewCleanup(int $days): int
    {
        return \App\Models\Invitation::where('expires_at', '<=', now()->subDays($days))
            ->whereNotIn('status', ['accepted', 'expired'])
            ->count();
    }
    
    private function showCurrentStats(): void
    {
        $stats = $this->invitationService->getInvitationStats();
        
        $this->info("\n📊 Estadísticas actuales del sistema:");
        $this->table(
            ['Estado', 'Cantidad'],
            [
                ['Total', $stats['total']],
                ['Pendientes', $stats['pending']],
                ['Enviadas', $stats['sent']],
                ['Aceptadas', $stats['accepted']],
                ['Expiradas', $stats['expired']],
                ['Canceladas', $stats['cancelled']],
            ]
        );
    }
}
