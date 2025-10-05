<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes; // si tu tabla tiene deleted_at
use Illuminate\Support\Str;

class Brand extends Model
{
    // use SoftDeletes; -> activar cuando se pueda implementar método restore en controlador + lógica de restauración en front
    
    protected $fillable = ['branch_id', 'name'];
    
    public function products(): HasMany { 
        return $this->hasMany(Product::class); 
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Brand $b) {
            // Si cambió el name y NO me enviaron slug en este request, lo genero
            if ($b->isDirty('name') && !$b->isDirty('slug')) {
                $b->slug = Str::slug($b->name);
            }
        });
    }
}
