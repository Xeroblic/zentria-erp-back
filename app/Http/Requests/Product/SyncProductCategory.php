<?php
namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;

class SyncProductCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {   /** @var Product $product */
        $product = $this->route('product');
        return $this->user()->can('update', $product); }

    public function rules(): array
    {   return [
            'category_ids' => ['required','array'],
            'category_ids.*' => ['integer','exists:categories,id'],
        ];
    }
}
