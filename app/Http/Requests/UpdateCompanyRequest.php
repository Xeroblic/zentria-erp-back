<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasRole('super-admin') ||
            Auth::user()?->hasRole('company-admin');
    }


    public function rules(): array
    {

        $companyId = Company::first()-> id ?? null;
        return [
            'company_name' => 'sometimes|string|max:100',
            'contact_email' => "sometimes|email|unique:companies,contact_email,{$companyId}",
            'company_rut' => "sometimes|string|max:15|unique:companies,company_rut,{$companyId}",
            'company_website' => 'nullable|url|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_type' => 'nullable|string|max:50',
            'business_activity' => 'nullable|string|max:255',
            'representative_name' => 'nullable|string|max:100',
            'company_address' => 'nullable|string|max:255',
            'commune_id' => 'nullable|integer|exists:communes,id',
            'legal_name' => 'nullable|string|max:100',
            'company_logo' => 'nullable|string|max:255',
        ];
    }
}
