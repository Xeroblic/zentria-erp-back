<?php
namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid','token','email','first_name','last_name','rut','position','phone_number','address',
        'invited_by','branch_id','role_name','permissions','temporary_password',
        'status','expires_at','sent_at','accepted_at','data'
    ];

    protected $casts = [
        'permissions' => 'array',
        'data' => 'array',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'status' => InvitationStatus::class,
    ];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function inviter(): BelongsTo { return $this->belongsTo(User::class, 'invited_by'); }
}
