<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class ScopeRole extends Model {
    protected $fillable = ['user_id', 'role_id', 'scope_type', 'scope_id'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function scopeEntity() {
        return $this->morphTo(__FUNCTION__, 'scope_type', 'scope_id');
    }

    // Métodos auxiliares para asignar roles contextuales
    public static function assignContextRole($userId, $roleName, $scopeType, $scopeId)
    {
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            throw new \Exception("Role {$roleName} not found");
        }

        return self::firstOrCreate([
            'user_id' => $userId,
            'role_id' => $role->id,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
        ]);
    }

    public static function removeContextRole($userId, $roleName, $scopeType, $scopeId)
    {
        return self::whereHas('role', fn($q) => $q->where('name', $roleName))
            ->where('user_id', $userId)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->delete();
    }

    // Scopes útiles
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForScope($query, $scopeType, $scopeId)
    {
        return $query->where('scope_type', $scopeType)->where('scope_id', $scopeId);
    }
}

