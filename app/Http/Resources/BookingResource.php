<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstSchedule = $this->schedules->sortBy('start_time')->first();
        $lastSchedule = $this->schedules->sortBy('start_time')->last();

        $formattedDate = '';
        $formattedTime = '';
        $fieldName = 'Field';

        if ($firstSchedule) {
            $formattedDate = Carbon::parse($firstSchedule->date)->format('d M Y');
            $fieldName = $firstSchedule->field->name ?? 'Field';
            
            if ($lastSchedule) {
                $startTime = Carbon::parse($firstSchedule->start_time)->format('H:i');
                $endTime = Carbon::parse($lastSchedule->end_time)->format('H:i');
                $formattedTime = "{$startTime}-{$endTime}";
            }
        }

        $fieldImageUrl = null;
        $fieldCategory = 'Umum';

        if ($firstSchedule && $firstSchedule->field) {
            $field = $firstSchedule->field;
            $fieldCategory = $field->category;
            $fieldImageUrl = $field->image_url
                ? (filter_var($field->image_url, FILTER_VALIDATE_URL) ? $field->image_url : asset('storage/' . $field->image_url))
                : null;
        }

        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'status' => $this->status,
            'booking_type' => $this->booking_type,
            'total_price' => (int) $this->total_price,
            'file_url' => $this->file_url
                ? (filter_var($this->file_url, FILTER_VALIDATE_URL) ? $this->file_url : asset('storage/' . $this->file_url))
                : null,
            'qr_id' => $this->qr_id,
            'qr_string' => $this->qr_string,
            'qr_image_url' => $this->qr_id ? "https://" . (config('services.midtrans.is_production') ? "api.midtrans.com" : "api.sandbox.midtrans.com") . "/v2/qris/" . $this->qr_id . "/qr-code" : null,
            'is_attended' => $this->is_attended,
            'attended_at' => $this->attended_at ? Carbon::parse($this->attended_at)->toIso8601String() : null,
            'expires_at' => $this->expires_at ? Carbon::parse($this->expires_at)->toIso8601String() : null,
            'is_reviewed' => $this->rating !== null,
            'field_name' => $fieldName,
            'field_image_url' => $fieldImageUrl,
            'field_category' => $fieldCategory,
            'formatted_date' => $formattedDate,
            'formatted_time' => $formattedTime,
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'created_at' => Carbon::parse($this->created_at)->toIso8601String(),
        ];
    }
}