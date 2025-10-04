<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'token',
        'email',
        'first_name',
        'last_name',
        'rut',
        'position',
        'phone_number',
        'address',
        'invited_by',
        'company_id',
        'subsidiary_id', 
        'branch_id',
        'role_name',
        'permissions',
        'temporary_password',
        'status',
        'expires_at',
        'sent_at',
        'accepted_at',
        'data'
    ];

    protected $casts = [
        'permissions' => 'array',
        'data' => 'array',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    // Estados de invitación
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Relaciones
     */
    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function subsidiary()
    {
        return $this->belongsTo(Subsidiary::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
                    ->whereIn('status', [self::STATUS_PENDING, self::STATUS_SENT]);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
                    ->where('status', '!=', self::STATUS_ACCEPTED);
    }

    /**
     * Métodos de utilidad
     */
    public function generateTokens()
    {
        $this->uid = Str::uuid()->toString();
        $this->token = Str::random(32);
        $this->temporary_password = Str::random(10);
        $this->expires_at = now()->addHours(48); // 48 horas para aceptar
    }

    public function isValid(): bool
    {
        return $this->expires_at > now() && 
               in_array($this->status, [self::STATUS_PENDING, self::STATUS_SENT]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at <= now() && $this->status !== self::STATUS_ACCEPTED;
    }

    public function markAsSent()
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now()
        ]);
    }

    public function markAsAccepted()
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now()
        ]);
    }

    public function markAsExpired()
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    public function cancel()
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function getActivationUrl(): string
    {
        return config('app.frontend_url') . "/activar/{$this->uid}/{$this->token}";
    }

    /**
     * Validar que el UID y token coinciden
     */
    public static function findByUidAndToken(string $uid, string $token): ?self
    {
        return self::where('uid', $uid)
                   ->where('token', $token)
                   ->valid()
                   ->first();
    }

    /**
     * Boot method para auto-generar tokens
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->uid) || empty($invitation->token)) {
                $invitation->generateTokens();
            }
            
            if (empty($invitation->status)) {
                $invitation->status = self::STATUS_PENDING;
            }
        });
    }
}
