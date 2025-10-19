<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
