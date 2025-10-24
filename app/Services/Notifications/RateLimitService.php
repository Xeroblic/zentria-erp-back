<?php

namespace App\Services\Notifications;

class RateLimitService
{
    public function allow(string $key, int $max, int $windowSeconds): bool
    {
        return true;
    }
}

