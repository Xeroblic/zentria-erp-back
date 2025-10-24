<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id', 'event_id', 'status', 'assigned_to', 'delivered_channels', 'read_at', 'ack_at', 'aggregate_count', 'last_occurred_at',
    ];

    protected $casts = [
        'delivered_channels' => 'array',
        'read_at' => 'datetime',
        'ack_at' => 'datetime',
        'last_occurred_at' => 'datetime',
    ];
}

