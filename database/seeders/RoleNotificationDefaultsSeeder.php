<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Notifications\NotificationType;

class RoleNotificationDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = DB::table('roles')->pluck('id','name');
        $types = NotificationType::all()->keyBy('key');

        $plans = [
            'company-admin' => [
                'transfer.sent' => ['allowed' => true],
                'transfer.receipt-discrepancy' => ['allowed' => true, 'priority_override' => 'P1'],
                'system.sequence-threshold' => ['allowed' => true, 'priority_override' => 'P1'],
                'system.sync-failed' => ['allowed' => true, 'priority_override' => 'P1'],
            ],
            'subsidiary-admin' => [
                'transfer.sent' => ['allowed' => true],
                'transfer.receipt-discrepancy' => ['allowed' => true, 'priority_override' => 'P1'],
            ],
            'branch-admin' => [
                'sale.delivery-due-today' => ['allowed' => true],
                'payment.confirmed' => ['allowed' => true],
            ],
            'company-member' => [
                'quote.expiring-soon' => ['allowed' => true],
                'quote.converted' => ['allowed' => true],
            ],
            'subsidiary-member' => [
                'quote.expiring-soon' => ['allowed' => true],
            ],
        ];

        foreach ($plans as $roleName => $map) {
            if (!isset($roles[$roleName])) continue;
            $roleId = $roles[$roleName];
            foreach ($map as $key => $cfg) {
                if (!isset($types[$key])) continue;
                DB::table('role_notification_defaults')->updateOrInsert(
                    ['role_id' => $roleId, 'notification_type_id' => $types[$key]->id],
                    [
                        'allowed' => $cfg['allowed'] ?? true,
                        'channels' => $cfg['channels'] ?? null,
                        'priority_override' => $cfg['priority_override'] ?? null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}

