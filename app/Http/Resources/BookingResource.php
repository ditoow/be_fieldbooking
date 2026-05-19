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
            'file_url' => $this->file_url ? asset('storage/' . $this->file_url) : null,
            'is_attended' => $this->is_attended,
            'attended_at' => $this->attended_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'schedule' => new ScheduleResource($this->whenLoaded('schedule')),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}