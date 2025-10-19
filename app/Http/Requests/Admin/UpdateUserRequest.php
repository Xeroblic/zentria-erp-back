<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'first_name'        => ['sometimes','required','string','max:120'],
            'middle_name'       => ['sometimes','nullable','string','max:120'],
            'last_name'         => ['sometimes','required','string','max:120'],
            'second_last_name'  => ['sometimes','nullable','string','max:120'],
            'position'          => ['sometimes','nullable','string','max:150'],
            'rut'               => ['sometimes','required','string','max:20', Rule::unique('users','rut')->ignore($userId)],
            'phone_number'      => ['sometimes','nullable','string','max:30'],
            'address'           => ['sometimes','nullable','string','max:255'],
            'date_of_birth'     => ['sometimes','nullable','date'],
            // 'email'             => ['sometimes','required','email','max:150', Rule::unique('users','email')->ignore($userId)], -> implementar verificación de email
            'gender'            => ['sometimes','nullable', Rule::in(['male','female','other'])],
        ];
    }
}
