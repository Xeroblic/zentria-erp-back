<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\SoftDeletes; // si tu tabla tiene deleted_at
use App\Models\Concerns\HasModelImages;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Str;

class Brand extends Model Implements HasMedia
{
    // use SoftDeletes; -> activar cuando se pueda implementar método restore en controlador + lógica de restauración en front
    
    use InteractsWithMedia, HasModelImages;

    protected $casts = ['is_active' => 'boolean'];

    protected $fillable = ['branch_id', 'name'];

    public static function primaryCollection(): string { return 'logo'; }

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile()->useDisk('public'); // solo un logo por marca
        $this->addMediaCollection('gallery')->useDisk('public'); // galería de imágenes de la marca
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media=null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400);
        $this->addMediaConversion('web')->format('webp')->width(1200);
    }
}
