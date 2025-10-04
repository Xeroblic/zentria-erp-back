<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'subsidiary_id',
        'branch_name',
        'branch_address',
        'branch_phone',
        'branch_email',
        'branch_status',
        'branch_manager_name',
        'branch_manager_phone',
        'branch_manager_email',
        'branch_opening_hours',
        'branch_location',
    ];

    public function subsidiary()
    {
        return $this->belongsTo(Subsidiary::class);
    }

    public function users() {
        return $this->belongsToMany(User::class)
            ->withPivot('is_primary', 'position')
            ->withTimestamps();
    }
}
