<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Http\Requests\Field\IndexFieldRequest;
use App\Http\Requests\Field\UploadFotoRequest;
use App\Http\Resources\FieldResource;
use App\Services\FieldService;

class FieldController extends Controller
{
    protected $fieldService;

    public function __construct(FieldService $fieldService)
    {
        $this->fieldService = $fieldService;
    }

    public function index(IndexFieldRequest $request) {
        $filters = $request->validated(); 
        $fields = $this->fieldService->getAllFields($filters);
        return FieldResource::collection($fields);
    }

    public function show($id)
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
