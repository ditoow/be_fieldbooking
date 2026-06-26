<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => ['nullable', 'string', 'regex:/^(08|\+62)[0-9]{8,13}$/'],
            'student_id' => [
                Rule::requiredIf(function () {
                    $email = $this->input('email');
                    return $email && str_ends_with($email, '@mhs.dinus.ac.id');
                }),
                'nullable',
                'string',
                'max:30',
                function ($attribute, $value, $fail) {
                    $email = $this->input('email');
                    if ($email && !str_ends_with($email, '@mhs.dinus.ac.id') && !empty($value)) {
                        $fail('NIM hanya boleh diisi oleh pengguna mahasiswa dengan email @mhs.dinus.ac.id.');
                    }
                }
            ],
        ];
    }

    /**
     * Get the validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'phone.regex' => 'Format nomor telepon tidak valid (08xx atau +62xx).',
            'student_id.required' => 'NIM wajib diisi untuk pendaftaran mahasiswa.',
            'student_id.max' => 'NIM maksimal 30 karakter.',
        ];
    }
}
