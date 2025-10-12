<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\Concerns\HasModelImages;
use Illuminate\Support\Str;


class Category extends Model implements HasMedia
{
    // use SoftDeletes; -> activar cuando se pueda implementar método restore en controlador + lógica de restauración en front

    use InteractsWithMedia, HasModelImages;
    
    protected $casts = ['is_active' => 'boolean'];

    protected $fillable = ['parent_id','name','slug'];

    public static function primaryCollection(): string { return 'banner'; }

    public function parent(): BelongsTo { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(Category::class, 'parent_id'); }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category')
            ->using(ProductCategory::class)
            ->withPivot(['assigned_at','deleted_at'])
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::saving(function (Category $c) {
            // Si cambió el name y NO me enviaron slug en este request, lo genero
            if ($c->isDirty('name') && !$c->isDirty('slug')) {
                $c->slug = Str::slug($c->name);
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')->singleFile()->useDisk('public');
        $this->addMediaCollection('gallery')->useDisk('public');
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400);
        $this->addMediaConversion('web')->format('webp')->width(1200);
    }

}
