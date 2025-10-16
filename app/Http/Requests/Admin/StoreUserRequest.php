<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'        => ['required','string','max:120'],
            'middle_name'       => ['nullable','string','max:120'],
            'last_name'         => ['required','string','max:120'],
            'second_last_name'  => ['nullable','string','max:120'],
            'position'          => ['nullable','string','max:150'],
            'rut'               => ['required','string','max:20','unique:users,rut'], // si validas RUT chileno, reemplaza por una Rule custom
            'phone_number'      => ['nullable','string','max:30'],
            'address'           => ['nullable','string','max:255'],
            'email'             => ['required','email','max:150','unique:users,email'],
            'password'          => ['required','string','min:8'],
            'is_active'         => ['sometimes','boolean'],
            'gender'            => ['nullable', Rule::in(['male','female','other'])], // ajusta a tus valores
            'primary_branch_id' => ['nullable','integer','exists:branches,id'],
        ];
    }
}
