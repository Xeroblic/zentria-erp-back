<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
