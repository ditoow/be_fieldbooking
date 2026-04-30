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
            'nama_lapangan' => $this->name,
            'deskripsi' => $this->description,
            'kategori' => $this->category,
            'status' => $this->status,
            'jadwal' => ScheduleResource::collection($this->whenLoaded('schedules')),
        ];

    }
}
