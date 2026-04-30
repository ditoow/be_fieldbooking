<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
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
            'field_id' => $this->field_id,
            'tanggal' => $this->date,
            'jam_mulai' => $this->start_time,
            'jam_selesai' => $this->end_time,
            'harga' => $this->price,
            'status' => $this->status,
        ];
    }
}
