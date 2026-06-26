<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class SimulateSuccessRequest extends FormRequest
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
            'booking_id' => 'required|integer|exists:bookings,id',
        ];
    }

    /**
     * Get the validation messages.
     */
    public function messages(): array
    {
        return [
            'booking_id.required' => 'ID booking wajib diisi.',
            'booking_id.integer' => 'ID booking harus berupa angka.',
            'booking_id.exists' => 'Booking tidak ditemukan.',
        ];
    }
}
