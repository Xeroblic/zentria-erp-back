<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Model;

class UserNotificationPreference extends Model
{
    protected $table = 'user_notification_preferences';

    protected $fillable = [
        'user_id', 'notification_type_id', 'allowed', 'channels', 'snooze_until', 'quiet_hours',
    ];

    protected $casts = [
        'allowed' => 'boolean',
        'channels' => 'array',
        'snooze_until' => 'datetime',
        'quiet_hours' => 'array',
    ];
}

