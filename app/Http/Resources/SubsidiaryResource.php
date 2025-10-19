<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Illuminate\Http\Resources\Json\JsonResource;

class SubsidiaryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'subsidiary_name' => $this->subsidiary_name,
            'subsidiary_rut' => $this->subsidiary_rut,
            'subsidiary_website' => $this->subsidiary_website,
            'subsidiary_phone' => $this->subsidiary_phone,
            'subsidiary_address' => $this->subsidiary_address,
            'subsidiary_email' => $this->subsidiary_email,
            'commune_id' => $this->commune_id,
            'subsidiary_manager_name' => $this->subsidiary_manager_name,
            'subsidiary_manager_phone' => $this->subsidiary_manager_phone,
            'subsidiary_manager_email' => $this->subsidiary_manager_email,
            'subsidiary_status' => $this->subsidiary_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'commune' => $this->whenLoaded('commune', function () {
                return [
                    'id' => $this->commune->id,
                    'name' => $this->commune->name,
                    'province_id' => $this->commune->province_id,
                ];
            }),

            'branches' => $this->whenLoaded('branches', function () {
                return $this->branches->map(function (Branch $branch) {
                    return [
                        'id' => $branch->id,
                        'subsidiary_id' => $branch->subsidiary_id,
                        'branch_name' => $branch->branch_name,
                        'branch_address' => $branch->branch_address,
                        'commune_id' => $branch->commune_id,
                        'branch_phone' => $branch->branch_phone,
                        'branch_email' => $branch->branch_email,
                        'branch_status' => $branch->branch_status,
                        'branch_manager_name' => $branch->branch_manager_name,
                        'branch_manager_phone' => $branch->branch_manager_phone,
                        'branch_manager_email' => $branch->branch_manager_email,
                        'branch_opening_hours' => $branch->branch_opening_hours,
                        'branch_location' => $branch->branch_location,
                        'commune' => $branch->relationLoaded('commune') && $branch->commune ? [
                            'id' => $branch->commune->id,
                            'name' => $branch->commune->name,
                            'province_id' => $branch->commune->province_id,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}

