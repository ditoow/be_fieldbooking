<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $emailRules = ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId];

        if ($user->hasRole('mahasiswa')) {
            $emailRules[] = 'regex:/@mhs\.dinus\.ac\.id$/';
        } elseif ($user->hasRole('umum')) {
            $emailRules[] = 'not_regex:/@mhs\.dinus\.ac\.id$/';
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'regex:/^(08|\+62)[0-9]{8,13}$/'],
            'student_id' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'email.regex' => 'Email mahasiswa harus menggunakan domain @mhs.dinus.ac.id.',
            'email.not_regex' => 'Email pengguna umum tidak boleh menggunakan domain mahasiswa.',
            'phone.regex' => 'Format nomor telepon tidak valid. Gunakan 08 atau +62.',
        ];
    }
}
