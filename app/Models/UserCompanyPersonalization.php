<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCompanyPersonalization extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'tema',
        'font_size',
        'preferred_subsidiary_id',
        'preferred_branch_id',
        'dashboard_widgets',
        'sidebar_collapsed',
        'language',
    ];

    protected $casts = [
        'dashboard_widgets' => 'array',
        'sidebar_collapsed' => 'boolean',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function preferredSubsidiary()
    {
        return $this->belongsTo(Subsidiary::class, 'preferred_subsidiary_id');
    }

    public function preferredBranch()
    {
        return $this->belongsTo(Branch::class, 'preferred_branch_id');
    }

    // Métodos auxiliares
    public static function getOrCreateForUser($userId, $companyId)
    {
        return self::firstOrCreate(
            ['user_id' => $userId, 'company_id' => $companyId],
            [
                'tema' => 'light',
                'font_size' => 14,
                'language' => 'es',
                'sidebar_collapsed' => false,
            ]
        );
    }
}
