<?php
// app/Models/Media/Media.php
namespace App\Models\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends BaseMedia
{
    protected $fillable = ['branch_id'];
}
