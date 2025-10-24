<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Model;

class NotificationDeliveryLog extends Model
{
    protected $table = 'notification_delivery_logs';

    protected $fillable = [
        'user_notification_id', 'channel', 'delivered_at', 'status', 'error',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
    ];
}

