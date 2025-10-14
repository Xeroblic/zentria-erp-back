<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;


class User extends Authenticatable implements JWTSubject , HasMedia
{
    use HasRoles, InteractsWithMedia;

    protected $guard_name = 'api';

    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'second_last_name',
        'position', 'rut', 'phone_number', 'address',
        'email', 'email_verified_at', 'password',
        'is_active', 'gender', 'image',
        'primary_branch_id',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    /* -------------------------------------------------
     | Relaciones
     --------------------------------------------------*/

    // Preferencia UX (opcional). No define pertenencia real.
    public function primaryBranch()
    {
        return $this->belongsTo(Branch::class, 'primary_branch_id');
    }

    // Pertenencia real M:N
    public function branches() {
        return $this->belongsToMany(Branch::class)
            ->withPivot('is_primary', 'position')
            ->withTimestamps();
    }

    // Revisa si el usuario tiene un rol X en contexto Y
    public function hasContextRole($roleName, $scopeType, $scopeId) {
        return ScopeRole::where('user_id', $this->id)
            ->whereHas('role', fn($q) => $q->where('name', $roleName))
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->exists();
    }

    // Retorna todas las scope_roles del usuario
    public function scopeRoles() {
        return $this->hasMany(ScopeRole::class);
    }

    // Verificar acceso jerárquico mejorado
    public function canAccessEntity($entityType, $entityId) {
        // Super admin siempre puede
        if ($this->hasRole('super-admin')) return true;

        switch ($entityType) {
            case 'company':
                return $this->companies->contains('id', $entityId) ||
                       $this->hasContextRole('company-admin', 'company', $entityId);
                       
            case 'subsidiary':
                $subsidiary = Subsidiary::find($entityId);
                return $subsidiary && (
                    $this->companies->contains('id', $subsidiary->company_id) ||
                    $this->hasContextRole('subsidiary-admin', 'subsidiary', $entityId) ||
                    $this->hasContextRole('company-admin', 'company', $subsidiary->company_id)
                );
                
            case 'branch':
                $branch = Branch::find($entityId);
                return $branch && (
                    $this->branches->contains('id', $entityId) ||
                    $this->hasContextRole('branch-admin', 'branch', $entityId) ||
                    $this->hasContextRole('subsidiary-admin', 'subsidiary', $branch->subsidiary_id) ||
                    $this->canAccessEntity('company', $branch->subsidiary->company_id)
                );
        }
        
        return false;
    }

    // Obtener todos los usuarios bajo el contexto de este usuario
    public function getUsersInScope() {
        if ($this->hasRole('super-admin')) {
            return User::all();
        }

        $userIds = collect();

        // Por cada empresa donde tiene rol de admin
        foreach ($this->companies as $company) {
            if ($this->hasContextRole('company-admin', 'company', $company->id)) {
                $companyUserIds = $company->users()->pluck('users.id');
                $userIds = $userIds->merge($companyUserIds);
            }
        }

        // Por sucursales donde es admin
        $subsidiaryAdminRoles = $this->scopeRoles()
            ->where('scope_type', 'subsidiary')
            ->whereHas('role', fn($q) => $q->where('name', 'subsidiary-admin'))
            ->get();

        foreach ($subsidiaryAdminRoles as $role) {
            $subsidiary = Subsidiary::find($role->scope_id);
            if ($subsidiary) {
                $branches = $subsidiary->branches()->pluck('id');
                $subsidiaryUserIds = User::whereHas('branches', fn($q) => $q->whereIn('branches.id', $branches))
                    ->pluck('id');
                $userIds = $userIds->merge($subsidiaryUserIds);
            }
        }

        // Por sucursales donde trabaja directamente
        foreach ($this->branches as $branch) {
            if ($this->hasContextRole('branch-admin', 'branch', $branch->id)) {
                $branchUserIds = $branch->users()->pluck('users.id');
                $userIds = $userIds->merge($branchUserIds);
            }
        }

        return User::whereIn('id', $userIds->unique())->get();
    }


    // Relación directa con empresas (M:N)
    public function companies()
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('is_primary', 'position_in_company', 'joined_at')
            ->withTimestamps();
    }

    // Empresa principal del usuario
    public function primaryCompany()
    {
        return $this->companies()->wherePivot('is_primary', true)->first();
    }

    // Si necesitas colecciones derivadas:
    public function subsidiaries()
    {
        return Subsidiary::whereIn('id', $this->branches()->pluck('subsidiary_id'))->get();
    }

    // Método mejorado para obtener empresas a través de sucursales
    public function companiesThroughBranches()
    {
        $subs = $this->branches()->pluck('subsidiary_id');
        return Company::whereIn('id', Subsidiary::whereIn('id', $subs)->pluck('company_id'))->get();
    }

    public function branch()
    {
        return $this->belongsTo((Branch::class), 'primary_branch_id');
    }
    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function personalization()
    {
        return $this->hasOne(UserPersonalization::class);
    }

    // Personalización por empresa
    public function companyPersonalizations()
    {
        return $this->hasMany(UserCompanyPersonalization::class);
    }

    // Obtener personalización para una empresa específica
    public function getPersonalizationForCompany($companyId)
    {
        return $this->companyPersonalizations()
            ->where('company_id', $companyId)
            ->first() ?: UserCompanyPersonalization::getOrCreateForUser($this->id, $companyId);
    }



    /* -------------------------------------------------
     | Helpers de permisos
     --------------------------------------------------*/
    public function hasPermission(string $permission): bool
    {
        return $this->hasPermissionTo($permission);
    }

    public function belongsToBranch(Branch $branch): bool
    {
        return $this->branches()->where('branches.id', $branch->id)->exists();
    }

    public function belongsToCompany($companyId): bool
    {
        return $this->branches()
            ->whereHas('subsidiary.company', fn($q) => $q->where('id', $companyId))
            ->exists();
    }

    public function canInviteUsers(): bool
    {
        return $this->hasAnyRole(['company-admin', 'branch-admin', 'super-admin']);
    }

    /* -------------------------------------------------
     | Scopes (vía pivote)
     --------------------------------------------------*/
    public function scopeByBranch($query, $branchId)
    {
        return $query->whereHas('branches', fn($q) => $q->where('branches.id', $branchId));
    }

    public function scopeBySubsidiary($query, $subsidiaryId)
    {
        return $query->whereHas('branches.subsidiary', fn($q) =>
            $q->where('subsidiaries.id', $subsidiaryId)
        );
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->whereHas('branches.subsidiary.company', fn($q) =>
            $q->where('companies.id', $companyId)
        );
    }

    public function scopeFromCompany($query, $companyId)
    {
        return $this->scopeByCompany($query, $companyId);
    }

    /* -------------------------------------------------
     | JWT
     --------------------------------------------------*/
    public function getJWTIdentifier(){ return $this->getKey(); }
    public function getJWTCustomClaims(){ return []; }

    /* -------------------------------------------------
     | Accessors
     --------------------------------------------------*/
    public function getFullNameAttribute()
    {
        $names = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->second_last_name,
        ]);
        return trim(implode(' ', $names));
    }
    public function setPrimaryBranch(int $branchId, ?string $position = null): void
    {
        $this->branches()->syncWithoutDetaching([
            $branchId => ['is_primary' => true, 'position' => $position]
        ]);

        DB::table('branch_user')
            ->where('user_id', $this->id)
            ->where('branch_id', '!=', $branchId)
            ->update(['is_primary' => false]);

        $this->primary_branch_id = $branchId;
        $this->save();
    }

    /* IMAGES */

    public function registerMediaCollections(): void
    {
        // Colección única para foto de perfil
        $this->addMediaCollection('avatar')
            ->singleFile()        // garantiza una sola imagen
            ->useDisk('public');  // debe resolver a storage/app/public + storage:link
    }

    public function registerMediaConversions(Media $media = null): void
    {
        // Conversión rápida para UI (no-queued si quieres previsualización inmediata)
        $this->addMediaConversion('avatar_sm')
            ->width(128)->height(128)
            ->nonQueued();

        $this->addMediaConversion('avatar_md')
            ->width(256)->height(256);

        $this->addMediaConversion('avatar_lg')
            ->width(512)->height(512);
    }

    // Accesor conveniente, usa md por defecto y placeholder si no hay avatar
    public function getAvatarUrlAttribute(): string
    {
        $url = $this->getFirstMediaUrl('avatar', 'avatar_md');
        return $url ?: asset('images/avatar-placeholder.png');
    }

}
