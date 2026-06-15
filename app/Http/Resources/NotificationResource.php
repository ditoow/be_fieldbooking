<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->data['title'] ?? '',
            'message' => $this->data['message'] ?? '',
            'type' => $this->data['type'] ?? 'info',
            'booking_id' => $this->data['booking_id'] ?? null,
            'read_at' => $this->read_at ? $this->read_at->toIso8601String() : null,
            'created_at' => $this->created_at->toIso8601String(),
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }
}
