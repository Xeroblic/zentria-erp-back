<?php
namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Product;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool {
        /** @var Product $product */
        $product = $this->route('product');
        return $this->user()->can('update', $product);
    }
    public function rules(): array {
        /** @var Product $product */
        $product = $this->route('product');
        return [
            'sku' => [
                'sometimes','string','max:255',
                Rule::unique('products','sku')->ignore($product->id)->where('branch_id', $product->branch_id),
            ],
            'commercial_sku' => ['sometimes','nullable','string','max:255'],
            'barcode' => ['sometimes','nullable','string','max:255'],
            'name' => ['sometimes','string','max:255'],
            'brand_id' => ['sometimes','exists:brands,id'],
            'product_type' => ['sometimes','string','max:255'],
            'condition_policy' => ['sometimes','string','max:255'],
            'serial_tracking' => ['sometimes','boolean'],
            'uom' => ['sometimes','string','max:255'],
            'warranty_months' => ['sometimes','integer','min:0'],
            'cost' => ['sometimes','numeric','min:0'],
            'price' => ['sometimes','numeric','min:0'],
            'attributes_json' => ['sometimes','nullable','array'],
            'is_active' => ['sometimes','boolean'],
            'category_ids' => ['sometimes','array'],
            'category_ids.*' => ['integer','exists:categories,id'],
        ];
    }
}
