<?php

namespace App\Services;

use App\Models\Field;
use Illuminate\Support\Facades\DB;

class FieldService
{
    protected SupabaseService $supabaseService;

    public function __construct(SupabaseService $supabaseService)
    {
        $this->supabaseService = $supabaseService;
    }

    public function createField($data)
    {
        if (isset($data['image_file']) && $data['image_file']->isValid()) {
            $data['image_url'] = $this->uploadImage($data['image_file']);
        }

        return DB::transaction(function () use ($data) {
            $field = Field::create([
                'name' => $data['name'],
                'image_url' => $data['image_url'] ?? null,
                'category' => $data['category'],
            ]);

            $field->detail()->create([
                'description' => $data['description'] ?? '',
                'surface_type' => $data['surface_type'] ?? 'vinyl',
                'rating' => $data['rating'] ?? 0.0,
                'status' => $data['status'] ?? 'available',
            ]);

            return $field;
        });
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
            return $q->whereHas('detail', function ($query) use ($status) {
                $query->where('status', $status);
            });
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

            $fieldData = array_intersect_key($data, array_flip(['name', 'image_url', 'category']));
            if (!empty($fieldData)) {
                $field->update($fieldData);
            }

            $detailData = array_intersect_key($data, array_flip(['description', 'surface_type', 'rating', 'status']));
            if (!empty($detailData)) {
                $field->detail()->update($detailData);
            }

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
        $result = $this->supabaseService->upload($file->getRealPath(), [
            'asset_folder' => 'field-images'
        ]);
        return $result['secure_url'];
    }
}
