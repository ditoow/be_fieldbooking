<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->detail?->description,
            'surface_type' => $this->detail?->surface_type,
            'rating' => (float) ($this->detail?->rating ?? 0.0),
            'image_url' => $this->image_url,
            'carousel_urls' => $this->detail?->carousel_urls ?? [],
            'category' => $this->category,
            'status' => $this->detail?->status,
            'price_min' => $this->price_min ?? null,
            'price_max' => $this->price_max ?? null,
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
        ];
    }
}
