<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleBookingRequest extends FormRequest
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
            'new_time_slot' => 'required|string|regex:/^\d{2}:00$/',
        ];
    }

    public function messages(): array
    {
        return [
            'field_id.required' => 'Lapangan wajib dipilih.',
            'field_id.exists' => 'Lapangan tidak ditemukan.',
            'date.required' => 'Tanggal wajib diisi.',
            'date.after_or_equal' => 'Tanggal harus hari ini atau setelahnya.',
            'new_time_slot.required' => 'Slot waktu baru wajib diisi.',
            'new_time_slot.regex' => 'Format slot waktu harus HH:00.',
        ];
    }
}
