<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCompanyRequest extends FormRequest
{

    public function authorize(): bool
    {
        return Auth::user()?->hasRole('super-admin');
        // || Auth::user()?->hasRole('company-admin');
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:100',
            'company_rut' => 'required|string|max:15|unique:companies',
            'contact_email' => 'required|email|unique:companies',
            'company_website' => 'nullable|url|max:255',
            'company_phone' => 'nullable|string|max:20',
            'representative_name' => 'nullable|string|max:100',
            'company_address' => 'nullable|string|max:255',
            'business_activity' => 'nullable|string|max:255',
            'legal_name' => 'nullable|string|max:100',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
            'company_type' => 'nullable|string|max:50',
            'subsidiaries' => 'array',
        ];
    }
}
