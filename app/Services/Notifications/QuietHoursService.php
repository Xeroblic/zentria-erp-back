<?php

namespace App\Services\Notifications;

use App\Models\User;

class QuietHoursService
{
    public function shouldDeferEmail(User $user, array $quietHours): bool
    {
        return false;
    }
}

