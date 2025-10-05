<?php
namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {   return $this->user()->can('create', \App\Models\Category::class); }

    public function rules(): array
    {   return [
            'name' => ['required','string','max:255'],
            'slug' => ['nullable','string','max:255','unique:categories,slug'],
            'parent_id' => ['nullable','integer','exists:categories,id'],
        ];
    }
}
