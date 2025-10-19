<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Company extends Model
{
    protected $fillable = [
        'company_name',
        'company_rut',
        'company_website',
        'company_phone',
        'representative_name',
        'contact_email',
        'company_address',
        'commune_id',
        'business_activity',
        'legal_name',
        'company_logo',
        'is_active',
        'company_type',
    ];

    // Relaciones directas
    public function subsidiaries()
    {
        return $this->hasMany(Subsidiary::class);
    }

    // Relación directa muchos a muchos con usuarios
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_primary', 'position_in_company', 'joined_at')
            ->withTimestamps();
    }

    // Usuarios a través de sucursales (método auxiliar)
    public function usersThroughBranches()
    {
        return $this->hasManyThrough(
            \App\Models\User::class,
            \App\Models\Branch::class,
            'subsidiary_id', 
            'branch_id',     
            'id',            
            'id'             
        );
    }

    public function branches()
    {
        return $this->hasManyThrough(
            \App\Models\Branch::class,
            \App\Models\Subsidiary::class,
            'company_id',
            'subsidiary_id',
            'id',
            'id'
        );
    }

    // Filtros por rol (requiere Spatie)
    public function technicians()
    {
        return $this->users()->role('technician');
    }

    public function administrators()
    {
        return $this->users()->role('branch-admin');
    }

    public function warehouseEmployees()
    {
        return $this->users()->role('warehouse-employee');
    }

    // Ubicación
    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeWithLogo($query)
    {
        return $query->whereNotNull('company_logo');
    }

    public function scopeWithoutLogo($query)
    {
        return $query->whereNull('company_logo');
    }

    // Visibilidad por usuario: acceso directo por company-member o indirecto por pertenencia a branches
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasRole('super-admin')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            // Company-member directo
            $q->whereExists(function ($sq) use ($user) {
                $sq->select(DB::raw(1))
                    ->from('scope_roles as sr')
                    ->join('roles as r', 'r.id', '=', 'sr.role_id')
                    ->whereColumn('sr.scope_id', 'companies.id')
                    ->where('sr.scope_type', 'company')
                    ->where('r.name', 'company-member')
                    ->where('sr.user_id', $user->id);
            })
            // O que el usuario tenga branches dentro de esta compañía
            ->orWhereHas('subsidiaries.branches.users', function ($uq) use ($user) {
                $uq->where('users.id', $user->id);
            });
        });
    }
}
