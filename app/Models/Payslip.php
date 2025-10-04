<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $fillable = [
        'user_id',
        'entry_date',
        'vacation_days',
        'administrative_days',
        'work_permits',
        'worked_days',
        'daily_payment',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
