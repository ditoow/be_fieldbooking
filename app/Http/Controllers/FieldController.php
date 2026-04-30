<?php

namespace App\Http\Controllers;

use App\Http\Requests\Field\IndexFieldRequest;
use App\Http\Resources\FieldResource;
use Illuminate\Http\Request;
use App\Models\Field;
use App\Services\FieldService;

class FieldController extends Controller
{
    protected $fieldService;

    public function __construct(FieldService $fieldService)
    {
        $this->fieldService = $fieldService;
    }

    /**
    * @queryParam category string Contoh: futsal
    * @queryParam status string Contoh: available
    */
    public function index(IndexFieldRequest $request) {
        $filters = $request->validated(); 
        $fields = $this->fieldService->getAllFields($filters);
        return FieldResource::collection($fields);
    }

   
    public function store(Request $request){
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'status' => 'required|in:available,maintenance',
        ]);
       $field = $this->fieldService->createField($validatedData);
       return new FieldResource($field);
    }

    public function show($id)
{
    $field = $this->fieldService->getFieldById($id);
    return new FieldResource($field);
}
}
