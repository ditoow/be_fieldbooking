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
            'is_attended' => $this->is_attended,
            'attended_at' => $this->attended_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'field_name' => $fieldName,
            'formatted_date' => $formattedDate,
            'formatted_time' => $formattedTime,
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}