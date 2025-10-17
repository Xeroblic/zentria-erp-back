<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Region extends Model
{

    use HasFactory;

    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
        'ordinal',
        'geographic_order',
    ];

    protected $casts = [
        'id' => 'integer',
        'geographic_order' => 'integer',
    ];


    public function provinces()
    {
        return $this->hasMany(Province::class);
    }
    
}
