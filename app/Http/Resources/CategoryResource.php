<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {   return [
            'id'=>$this->id,
            'name'=>$this->name,
            'parent_id'=>$this->parent_id,
            'slug'=>$this->slug,
            'image'   => $this->primaryImagePayload(),
            'gallery' => $this->galleryPayload(),
            'children_count'=>$this->whenCounted('children'),
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,
        ];
    }
}
