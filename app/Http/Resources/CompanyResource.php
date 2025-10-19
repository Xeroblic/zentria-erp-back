<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\SubsidiaryResource;

class CompanyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'company_rut' => $this->company_rut,
            'company_website' => $this->company_website,
            'company_phone' => $this->company_phone,
            'representative_name' => $this->representative_name,
            'contact_email' => $this->contact_email,
            'company_address' => $this->company_address,
            'commune_id' => $this->commune_id,
            'commune' => $this->whenLoaded('commune', function () {
                return [
                    'id' => $this->commune->id,
                    'name' => $this->commune->name,
                    'province_id' => $this->commune->province_id,
                ];
            }),
            'business_activity' => $this->business_activity,
            'legal_name' => $this->legal_name,
            'company_logo' => $this->company_logo,
            'is_active' => (bool) $this->is_active,
            'company_type' => $this->company_type,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,

            // Subsidiarias usando su Resource para salida consistente
            'subsidiaries' => SubsidiaryResource::collection($this->whenLoaded('subsidiaries')),

            'pivot' => $this->pivot ? [
                'rol_id' => $this->pivot->rol_id,
                'empresa_id' => $this->pivot->empresa_id,
                'usuario_id' => $this->pivot->usuario_id,
            ] : null,
        ];
    }
}
