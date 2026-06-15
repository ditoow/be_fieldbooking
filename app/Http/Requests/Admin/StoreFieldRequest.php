<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'status' => 'required|in:available,maintenance',
            'surface_type' => 'nullable|in:vinyl,parket,semen',
            'image_file' => 'nullable|image|max:2048',
        ];
    }
}
