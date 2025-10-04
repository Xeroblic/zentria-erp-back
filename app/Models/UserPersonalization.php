<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPersonalization extends Model
{
    protected $table = 'user_personalizations';

    protected $fillable = [
        'user_id',
        'tema',
        'font_size',
        'sucursal_principal',
        'company_id',
        'tcolor',
        'tcolor_int',
    ];

    protected $casts = [
        'tema' => 'integer',
        'font_size' => 'integer',
        'sucursal_principal' => 'integer',
        'company_id' => 'integer',
        'tcolor' => 'string',
        'tcolor_int' => 'string',
    ];

    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'sucursal_principal');
    }
}
