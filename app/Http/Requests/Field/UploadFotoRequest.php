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

    public function messages(): array
    {
        return [
            'foto.required' => 'Foto wajib diunggah.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Format foto harus jpg, jpeg, png, atau webp.',
            'foto.max' => 'Ukuran foto maksimal 5MB.',
        ];
    }
}
