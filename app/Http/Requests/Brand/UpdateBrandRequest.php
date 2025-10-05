<?php
namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Brand;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {   /** @var Brand $brand */
        $brand = $this->route('brand');
        return $this->user()->can('update', $brand); }

    public function rules(): array
    {   /** @var Brand $brand */
        $brand = $this->route('brand');
        return [
            'name' => [
                'sometimes','string','max:255',
                Rule::unique('brands','name')->ignore($brand->id)->where('branch_id', $brand->branch_id),
            ],
        ];
    }
}
