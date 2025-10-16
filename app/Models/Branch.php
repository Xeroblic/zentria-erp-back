<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class Branch extends Model Implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'subsidiary_id',
        'branch_name',
        'branch_address',
        'commune_id',
        'branch_phone',
        'branch_email',
        'branch_status',
        'branch_manager_name',
        'branch_manager_phone',
        'branch_manager_email',
        'branch_opening_hours',
        'branch_location',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('library')->useDisk('public'); // biblioteca propia de la sucursal
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media=null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400);
        $this->addMediaConversion('web')->format('webp')->width(1200);
    }

    public function subsidiary()
    {
        return $this->belongsTo(Subsidiary::class);
    }

    public function users() {
        return $this->belongsToMany(User::class)
            ->withPivot('is_primary', 'position')
            ->withTimestamps();
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
