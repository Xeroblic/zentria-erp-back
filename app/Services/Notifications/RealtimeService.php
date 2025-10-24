<?php

namespace App\Services\Notifications;

use App\Models\Notifications\UserNotification;

class RealtimeService
{
    public function emit(UserNotification $notification): void
    {
        // Placeholder SSE/WebSocket emission
    }
}

