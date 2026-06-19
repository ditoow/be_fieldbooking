<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field_id' => 'required|exists:fields,id',
            'date' => 'required|date|after_or_equal:today',
            'time_slots' => 'required|array|min:1',
            'time_slots.*' => 'string|regex:/^\d{2}:00$/',
        ];
    }

    public function messages(): array
    {
        return [
            'field_id.required' => 'Lapangan wajib dipilih.',
            'field_id.exists' => 'Lapangan tidak ditemukan.',
            'date.required' => 'Tanggal wajib diisi.',
            'date.after_or_equal' => 'Tanggal harus hari ini atau setelahnya.',
            'time_slots.required' => 'Slot waktu wajib dipilih.',
            'time_slots.*.regex' => 'Format slot waktu harus HH:00.',
        ];
    }
}
