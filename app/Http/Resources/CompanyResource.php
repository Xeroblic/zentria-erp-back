<?php

namespace App\Http\Resources;

use App\Models\Subsidiary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'business_activity' => $this->business_activity,
            'legal_name' => $this->legal_name,
            'company_logo' => $this->company_logo,
            'is_active' => (bool) $this->is_active,
            'company_type' => $this->company_type,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'subsidiaries' => $this->whenLoaded('subsidiaries', function () {
                return $this->subsidiaries->map(function (Subsidiary $subsidiary) {
                    return [
                        'id' => $subsidiary->id,
                        'name' => $subsidiary->subsidiary_name,
                        'address' => $subsidiary->subsidiary_address,
                        'phone' => $subsidiary->subsidiary_phone,
                        'email' => $subsidiary->contact_email,
                    ];
                });
            }, []), // ← Valor por defecto: array vacío
            'pivot' => $this->pivot ? [
                'rol_id' => $this->pivot->rol_id,
                'empresa_id' => $this->pivot->empresa_id,
                'usuario_id' => $this->pivot->usuario_id,
            ] : null,
        ];
    }
}
