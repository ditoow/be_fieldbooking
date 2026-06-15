<?php

namespace App\Http\Requests\Field;

use Illuminate\Foundation\Http\FormRequest;

class UploadFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
