<?php
namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Branch;
use App\Models\Product;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool {
        /** @var Branch $branch */
        $branch = $this->route('branch');
        return $this->user()->can('create', [Product::class, $branch]);
    }
    public function rules(): array {
        /** @var Branch $branch */
        $branch = $this->route('branch');
        return [
            'sku' => [
                'required','string','max:255',
                Rule::unique('products','sku')->where('branch_id', $branch->id),
            ],
            'commercial_sku' => ['nullable','string','max:255'],
            'barcode' => ['nullable','string','max:255'],
            'name' => ['required','string','max:255'],
            'brand_id' => ['required','exists:brands,id'],
            'product_type' => ['nullable','string','max:255'],
            'warranty_months' => ['integer','min:0'],
            'serial_tracking' => ['boolean'],
            'short_description' => ['nullable','string'],
            'long_description' => ['nullable','string'],
            'stock' => ['integer','min:0'],
            'snippet_description' => ['nullable','string'],
            'cost' => ['numeric','min:0'],
            'price' => ['required', 'numeric','min:0'],
            'offer_price' => ['nullable', 'numeric','min:0', 'lte:price'],
            'product_status' => ['nullable','string','max:255'],
            'attributes_json' => ['nullable','array'], // jsonb
            'is_active' => ['required', 'boolean'],
            'category_ids' => ['required', 'array'],
            'category_ids.*' => ['integer','exists:categories,id'],
        ];
    }
}
