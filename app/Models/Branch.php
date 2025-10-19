<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class Branch extends Model Implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'subsidiary_id',
        'branch_name',
        'branch_address',
        'commune_id',
        'branch_phone',
        'branch_email',
        'branch_status',
        'branch_manager_name',
        'branch_manager_phone',
        'branch_manager_email',
        'branch_opening_hours',
        'branch_location',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('library')->useDisk('public'); // biblioteca propia de la sucursal
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media=null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400);
        $this->addMediaConversion('web')->format('webp')->width(1200);
    }

    public function subsidiary()
    {
        return $this->belongsTo(Subsidiary::class);
    }

    public function users() {
        return $this->belongsToMany(User::class)
            ->withPivot('is_primary', 'position')
            ->withTimestamps();
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Ubicación
    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    // Scoping de visibilidad por usuario (herencia: company > subsidiary > branch)
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasRole('super-admin')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            // Acceso directo por pivot branch_user
            $q->whereHas('users', function ($uq) use ($user) {
                $uq->where('users.id', $user->id);
            })
            // Acceso heredado por subsidiary-member
            ->orWhereExists(function ($sq) use ($user) {
                $sq->select(DB::raw(1))
                    ->from('scope_roles as sr')
                    ->join('roles as r', 'r.id', '=', 'sr.role_id')
                    ->whereColumn('sr.scope_id', 'branches.subsidiary_id')
                    ->where('sr.scope_type', 'subsidiary')
                    ->where('r.name', 'subsidiary-member')
                    ->where('sr.user_id', $user->id);
            })
            // Acceso heredado por company-member
            ->orWhereExists(function ($sq) use ($user) {
                $sq->select(DB::raw(1))
                    ->from('scope_roles as sr')
                    ->join('roles as r', 'r.id', '=', 'sr.role_id')
                    ->join('subsidiaries as s', 's.company_id', '=', 'sr.scope_id')
                    ->whereColumn('s.id', 'branches.subsidiary_id')
                    ->where('sr.scope_type', 'company')
                    ->where('r.name', 'company-member')
                    ->where('sr.user_id', $user->id);
            });
        });
    }
}
