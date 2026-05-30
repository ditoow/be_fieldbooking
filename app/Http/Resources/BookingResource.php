<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'booking_type' => $this->booking_type,
            'total_price' => (int) $this->total_price,
            'file_url' => $this->file_url
                ? (filter_var($this->file_url, FILTER_VALIDATE_URL) ? $this->file_url : asset('storage/' . $this->file_url))
                : null,
            'is_attended' => $this->is_attended,
            'attended_at' => $this->attended_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}