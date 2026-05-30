<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_number' => $this->user_number,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'student_id' => $this->student_id,
            'role' => $this->getRoleNames()->first() ?? 'umum',
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
