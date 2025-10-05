<?php
namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Category;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {   /** @var Category $category */
        $category = $this->route('category');
        return $this->user()->can('update', $category); }

    public function rules(): array
    {   return [
            'name' => ['sometimes','string','max:255'],
            'parent_id' => ['sometimes','nullable','integer','exists:categories,id'],
            'slug' => ['sometimes','string','max:250','unique:categories,slug,'.$this->route('category')->id],
        ];
    }
}
