<?php

namespace App\Services;

use App\Models\Field;
use Illuminate\Support\Facades\DB;

class FieldService
{
    protected CloudinaryService $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function createField($data)
    {
        if (isset($data['image_file']) && $data['image_file']->isValid()) {
            $data['image_url'] = $this->uploadImage($data['image_file']);
        }

        $field = Field::create($data);
        return $field;
    }

    public function getAllFields($filters = [])
    {
        $query = Field::query();

        $query->withMin(['schedules as price_min' => function ($q) {
            $q->where('status', 'available');
        }], 'price');

        $query->withMax(['schedules as price_max' => function ($q) {
            $q->where('status', 'available');
        }], 'price');

        $query->when($filters['category'] ?? null, function ($q, $category){
            return $q->where('category', $category);
        });

        $query->when($filters['status'] ?? null, function ($q, $status){
            return $q->where('status', $status);
        });
        
        return $query->get();
    }

    public function getFieldById($id)
    {
        return Field::with(['schedules' => function ($q) {
                $q->where('status', 'available')->orderBy('start_time', 'asc');
            }])
            ->withMin(['schedules as price_min' => function ($q) {
                $q->where('status', 'available');
            }], 'price')
            ->withMax(['schedules as price_max' => function ($q) {
                $q->where('status', 'available');
            }], 'price')
            ->findOrFail($id);
    }

    public function updateField(Field $field, array $data)
    {
        return DB::transaction(function () use ($field, $data) {
            if (isset($data['image_file']) && $data['image_file']->isValid()) {
                $data['image_url'] = $this->uploadImage($data['image_file']);
            }

            $field->update(array_intersect_key($data, array_flip([
                'name', 'description', 'surface_type', 'image_url', 'category', 'status'
            ])));

            return $field->fresh();
        });
    }

    public function deleteField(Field $field)
    {
        DB::transaction(function () use ($field) {
            $field->schedules()->delete();
            $field->delete();
        });
    }

    protected function uploadImage($file): string
    {
        $result = $this->cloudinaryService->upload($file->getRealPath(), [
            'asset_folder' => 'field-images'
        ]);
        return $result['secure_url'];
    }
}
