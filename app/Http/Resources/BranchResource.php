<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'   => $this->id,
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
            'subsidiary_rut' => optional($this->subsidiary)->subsidiary_rut,
            'branch_created_at' => $this->branch_created_at,
            'branch_updated_at' => $this->branch_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
