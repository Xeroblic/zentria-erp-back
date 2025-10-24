<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Model;

class RoleNotificationDefault extends Model
{
    protected $table = 'role_notification_defaults';

    protected $fillable = [
        'role_id', 'notification_type_id', 'allowed', 'channels', 'priority_override',
    ];

    protected $casts = [
        'allowed' => 'boolean',
        'channels' => 'array',
    ];
}

