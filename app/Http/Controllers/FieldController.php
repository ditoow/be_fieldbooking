<?php

namespace App\Http\Controllers;

use App\Http\Requests\Field\IndexFieldRequest;
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
}
