<?php

namespace App\Http\Requests\Field;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexFieldRequest extends FormRequest
{
    public function authorize(): bool {
    return true; 
}
public function rules(): array {
    return [
        'category' => 'nullable|string',
        'status' => 'nullable|string|in:available,maintenance',
    ];
}
}
