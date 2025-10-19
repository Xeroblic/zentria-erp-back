<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubsidiaryBriefResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->subsidiary_name,
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
        ];
    }
}

