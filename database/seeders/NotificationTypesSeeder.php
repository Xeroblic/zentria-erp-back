<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notifications\NotificationType;

class NotificationTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['key' => 'transfer.sent', 'module' => 'transfer', 'default_priority' => 'P2', 'default_channels' => ['inapp','email'], 'critical' => false, 'enabled_global' => true],
            ['key' => 'transfer.receipt-discrepancy', 'module' => 'transfer', 'default_priority' => 'P1', 'default_channels' => ['inapp','email'], 'critical' => true, 'enabled_global' => true],
            ['key' => 'quote.expiring-soon', 'module' => 'quote', 'default_priority' => 'P3', 'default_channels' => ['inapp'], 'critical' => false, 'enabled_global' => true],
            ['key' => 'quote.converted', 'module' => 'quote', 'default_priority' => 'P2', 'default_channels' => ['inapp','email'], 'critical' => false, 'enabled_global' => true],
            ['key' => 'sale.delivery-due-today', 'module' => 'sales', 'default_priority' => 'P2', 'default_channels' => ['inapp','email'], 'critical' => false, 'enabled_global' => true],
            ['key' => 'payment.confirmed', 'module' => 'finance', 'default_priority' => 'P2', 'default_channels' => ['inapp','email'], 'critical' => false, 'enabled_global' => true],
            ['key' => 'system.sequence-threshold', 'module' => 'system', 'default_priority' => 'P1', 'default_channels' => ['inapp','email'], 'critical' => true, 'enabled_global' => true],
            ['key' => 'system.sync-failed', 'module' => 'system', 'default_priority' => 'P1', 'default_channels' => ['inapp','email'], 'critical' => true, 'enabled_global' => true],
        ];

        foreach ($types as $t) {
            NotificationType::updateOrCreate(['key' => $t['key']], $t);
        }
    }
}

