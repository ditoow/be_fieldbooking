<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fieldId = (int) $this->route('id');

        return [
            'name' => 'nullable|string|max:255|unique:fields,name,' . $fieldId,
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'status' => 'nullable|in:available,maintenance',
            'surface_type' => 'nullable|in:vinyl,parket,semen',
            'image_file' => 'nullable|image|max:2048',
            'carousel_urls' => 'nullable|array',
            'carousel_urls.*' => 'url',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama lapangan sudah digunakan.',
            'status.in' => 'Status harus available atau maintenance.',
            'surface_type.in' => 'Tipe permukaan harus vinyl, parket, atau semen.',
            'image_file.image' => 'File harus berupa gambar.',
            'image_file.max' => 'Ukuran gambar maksimal 2MB.',
            'carousel_urls.*.url' => 'Format URL carousel tidak valid.',
        ];
    }
}
