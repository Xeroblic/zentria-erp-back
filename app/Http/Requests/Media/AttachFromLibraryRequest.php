<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class AttachFromLibraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // aplica tu Policy/Permission si corresponde
    }

    public function rules(): array
    {
        return [
            'library_media_id' => ['required','integer','exists:media,id'],
            'collection'       => ['nullable','string','max:50'],
            'sort_order'       => ['nullable','integer','min:0'],
            'alt_text'         => ['nullable','string','max:255'],
        ];
    }
}