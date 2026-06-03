<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Http\Resources\FieldResource;
use App\Services\FieldService;
use Illuminate\Http\Request;

class AdminFieldController extends Controller
{
    protected FieldService $fieldService;

    public function __construct(FieldService $fieldService)
    {
        $this->fieldService = $fieldService;
    }

    public function storeField(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'status' => 'required|in:available,maintenance',
            'surface_type' => 'nullable|in:vinyl,parket,semen',
            'image_file' => 'nullable|image|max:2048',
        ]);

        $field = $this->fieldService->createField($validatedData);
        return new FieldResource($field);
    }

    public function updateField(Request $request, $id)
    {
        $field = Field::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'status' => 'nullable|in:available,maintenance',
            'surface_type' => 'nullable|in:vinyl,parket,semen',
            'image_file' => 'nullable|image|max:2048',
        ]);

        $updatedField = $this->fieldService->updateField($field, $validatedData);
        return new FieldResource($updatedField);
    }

    public function destroyField($id)
    {
        $field = Field::findOrFail($id);
        $this->fieldService->deleteField($field);

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas lapangan berhasil dihapus.'
        ]);
    }
}
