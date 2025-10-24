<?php

namespace App\Services\Notifications;

class DeduplicationService
{
    public function shouldAggregate(string $dedupKey, int $ttlSeconds): bool
    {
        return false;
    }
}

