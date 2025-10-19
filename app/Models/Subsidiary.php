<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Subsidiary extends Model
{
    protected $fillable = [
        'company_id',
        'subsidiary_name',
        'subsidiary_rut',
        'subsidiary_website',
        'subsidiary_phone',
        'subsidiary_address',
        'commune_id',
        'subsidiary_email',
        'subsidiary_created_at',
        'subsidiary_updated_at',
        'subsidiary_manager_name',
        'subsidiary_manager_phone',
        'subsidiary_manager_email',
        'subsidiary_status',
    ];

    // Relaciones
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    // Ubicación
    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    // Scoping de visibilidad por usuario (herencia: company > subsidiary)
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasRole('super-admin')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            // Acceso por branches directas del usuario dentro de esta subsidiary
            $q->whereHas('branches.users', function ($uq) use ($user) {
                $uq->where('users.id', $user->id);
            })
            // Acceso directo por subsidiary-member
            ->orWhereExists(function ($sq) use ($user) {
                $sq->select(DB::raw(1))
                    ->from('scope_roles as sr')
                    ->join('roles as r', 'r.id', '=', 'sr.role_id')
                    ->whereColumn('sr.scope_id', 'subsidiaries.id')
                    ->where('sr.scope_type', 'subsidiary')
                    ->where('r.name', 'subsidiary-member')
                    ->where('sr.user_id', $user->id);
            })
            // Acceso heredado por company-member
            ->orWhereExists(function ($sq) use ($user) {
                $sq->select(DB::raw(1))
                    ->from('scope_roles as sr')
                    ->join('roles as r', 'r.id', '=', 'sr.role_id')
                    ->whereColumn('sr.scope_id', 'subsidiaries.company_id')
                    ->where('sr.scope_type', 'company')
                    ->where('r.name', 'company-member')
                    ->where('sr.user_id', $user->id);
            });
        });
    }
}
