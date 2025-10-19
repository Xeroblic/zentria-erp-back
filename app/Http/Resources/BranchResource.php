<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'subsidiary_id' => $this->subsidiary_id,
            'branch_name' => $this->branch_name,
            'branch_address' => $this->branch_address,
            'commune_id' => $this->commune_id,
            'branch_phone' => $this->branch_phone,
            'branch_email' => $this->branch_email,
            'branch_status' => $this->branch_status,
            'branch_manager_name' => $this->branch_manager_name,
            'branch_manager_phone' => $this->branch_manager_phone,
            'branch_manager_email' => $this->branch_manager_email,
            'branch_opening_hours' => $this->branch_opening_hours,
            'branch_location' => $this->branch_location,
        ];

        if ($this->relationLoaded('commune') && $this->commune) {
            $data['commune'] = [
                'id' => $this->commune->id,
                'name' => $this->commune->name,
                'province_id' => $this->commune->province_id,
            ];
        }

        return $data;
    }
}

