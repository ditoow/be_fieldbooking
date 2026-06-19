<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal wajib diisi.',
            'date.after_or_equal' => 'Tanggal harus hari ini atau setelahnya.',
            'start_time.date_format' => 'Format waktu mulai harus HH:mm.',
            'end_time.date_format' => 'Format waktu selesai harus HH:mm.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'reason.max' => 'Alasan maksimal 255 karakter.',
        ];
    }
}
