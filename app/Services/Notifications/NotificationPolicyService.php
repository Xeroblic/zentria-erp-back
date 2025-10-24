<?php

namespace App\Services\Notifications;

use App\Models\Notifications\NotificationType;
use App\Models\User;

class NotificationPolicyService
{
    public function resolveEffective(User $user, NotificationType $type, array $context): array
    {
        return [
            'allowed' => true,
            'channels' => $type->default_channels ?? ['inapp'],
            'priority' => $type->default_priority,
            'origin' => 'global',
            'locked' => (bool) $type->critical,
        ];
    }

    public function userCanReceive(User $user, array $eventScope): bool
    {
        return true;
    }
}

