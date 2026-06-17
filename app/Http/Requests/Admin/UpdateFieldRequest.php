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
}
