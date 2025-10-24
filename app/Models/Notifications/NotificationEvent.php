<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Model;

class NotificationEvent extends Model
{
    protected $fillable = [
        'type_id', 'entity_type', 'entity_id', 'company_id', 'subsidiary_id', 'branch_id', 'priority', 'payload', 'dedup_key', 'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];
}

