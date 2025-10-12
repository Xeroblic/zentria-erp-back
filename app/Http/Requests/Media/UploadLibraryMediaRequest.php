<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class UploadLibraryMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // aplica tu Policy/Permission si corresponde
    }

    public function rules(): array
    {
        return [
            'file'      => ['required','file','image','mimes:jpg,jpeg,png,webp,svg','max:32000'], // max 32MB
            'alt_text'  => ['nullable','string','max:255'],
            'tags'      => ['array'],
            'tags.*'    => ['string','max:50'],
        ];
    }
}