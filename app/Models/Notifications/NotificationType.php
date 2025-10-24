<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Model;

class NotificationType extends Model
{
    protected $fillable = [
        'key', 'module', 'description', 'default_priority', 'default_channels', 'critical', 'enabled_global',
    ];

    protected $casts = [
        'default_channels' => 'array',
        'critical' => 'boolean',
        'enabled_global' => 'boolean',
    ];
}

