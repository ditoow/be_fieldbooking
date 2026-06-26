<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Http\Requests\Field\IndexFieldRequest;
use App\Http\Requests\Field\UploadFotoRequest;
use App\Http\Resources\FieldResource;
use App\Models\Booking;
use App\Models\Field;
use App\Models\Rating;
use App\Models\User;
use App\Services\FieldService;

class FieldController extends Controller
{
    protected FieldService $fieldService;

    public function __construct(FieldService $fieldService)
    {
        $this->fieldService = $fieldService;
    }

    public function index(IndexFieldRequest $request) {
        $filters = $request->validated(); 
        $fields = $this->fieldService->getAllFields($filters);

        $avgRating = Rating::avg('rating');
        $satisfactionRate = $avgRating ? round(($avgRating / 5) * 100) : null;

        $stats = [
            'total_bookings' => Booking::count(),
            'total_fields' => Field::count(),
            'active_users' => User::count(),
            'satisfaction_rate' => $satisfactionRate,
        ];

        return FieldResource::collection($fields)->additional(['meta' => ['stats' => $stats]]);
    }

    public function show(int $id)
    {
        $field = $this->fieldService->getFieldById($id);
        return new FieldResource($field);
    }


    public function uploadFoto(UploadFotoRequest $request)
    {
        $result = $this->fieldService->uploadFoto($request->file('foto'));

        return response()->json(['message' => 'Photo uploaded successfully', 'url' => $result['url']]);
    }
}
