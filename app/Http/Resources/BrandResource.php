<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray($request)
    {   return [
            'id'=>$this->id,
            'branch_id'=>$this->branch_id,
            'name'=>$this->name,
            'slug'=>$this->slug,
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,
        ];
    }
}
