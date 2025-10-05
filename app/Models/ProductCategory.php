<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Pivot
{
    use SoftDeletes;
    protected $table = 'product_category';
    public $incrementing = false;
    protected $dates = ['assigned_at','deleted_at'];
}
