<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commune extends Model
{

    use HasFactory;

    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
        'province_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'province_id' => 'integer',
    ];


    public function province()
    {
        return $this->belongsTo(Province::class);
    }
    
}
