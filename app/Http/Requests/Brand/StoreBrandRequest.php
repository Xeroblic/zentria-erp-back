<?php
namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Branch;
use App\Models\Brand;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {   $branch = $this->route('branch');
        return $this->user()->can('create', [Brand::class, $branch]); 
    }

    public function rules(): array
    {   /** @var Branch $branch */
        $branch = $this->route('branch');
        return [
            'name' => [
                'required','string','max:255',
                Rule::unique('brands','name')->where('branch_id', $branch->id),
            ],
        ];
    }
    
}
