<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFieldRequest;
use App\Http\Requests\Admin\UpdateFieldRequest;
use App\Models\Field;
use App\Http\Resources\FieldResource;
use App\Services\FieldService;

class AdminFieldController extends Controller
{
    protected FieldService $fieldService;

    public function __construct(FieldService $fieldService)
    {
        $this->fieldService = $fieldService;
    }

    public function storeField(StoreFieldRequest $request)
    {
        $field = $this->fieldService->createField($request->validated());
        return new FieldResource($field);
    }

    public function updateField(UpdateFieldRequest $request, string $id)
    {
        $field = Field::findOrFail($id);

        $updatedField = $this->fieldService->updateField($field, $request->validated());
        return new FieldResource($updatedField);
    }

    public function destroyField(string $id)
    {
        $field = Field::findOrFail($id);
        $this->fieldService->deleteField($field);

        return response()->json([
            'success' => true,
            'message' => 'Field deleted successfully.'
        ]);
    }
}
