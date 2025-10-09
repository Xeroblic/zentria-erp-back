<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id','sku','commercial_sku','barcode','name','brand_id',
        'product_type', 'serial_tracking', 'warranty_months','cost',
        'price', 'offer_price','attributes_json','is_active',
        'short_description','long_description','stock','snippet_description',
    ];

    protected $casts = [
        'serial_tracking' => 'boolean',
        'is_active' => 'boolean',
        'attributes_json' => 'array', // jsonb ⇄ array
        'cost' => 'decimal:2',
        'price' => 'decimal:2',
        'offer_price' => 'decimal:2',
    ];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }

    public function categories()
    {
    return $this->belongsToMany(Category::class, 'product_category')
        ->withPivot(['assigned_at','deleted_at'])
        ->withTimestamps();
    }

    // Scopes
    public function scopeFromBranch(Builder $q, int|string $branchId): Builder { return $q->where('branch_id', $branchId); }

    public function scopeSearch(Builder $q, ?string $term): Builder {
        if (!$term = trim((string)$term)) return $q;
        // Postgres: ILIKE para case-insensitive
        return $q->where(function($w) use ($term){
            $w->where('name','ILIKE',"%{$term}%")
              ->orWhere('sku','ILIKE',"%{$term}%")
              ->orWhere('barcode','ILIKE',"%{$term}%");
        });
    }

    
}
