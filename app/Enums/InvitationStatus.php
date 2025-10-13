<?php
namespace App\Enums;

enum InvitationStatus: string
{
    case PENDING = 'pending';
    case USED = 'used';
    case EXPIRED = 'expired';
}
