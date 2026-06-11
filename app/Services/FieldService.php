<?php

namespace App\Services;

use App\Models\Field;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Laravel\Facades\Image;

if (!class_exists(\Intervention\Image\Laravel\Facades\Image::class)) {
    class DummyImageFacade {
        public static function read($source) { return new self(); }
        public function scaleDown($width) { return $this; }
        public function toJpeg($quality) { return $this; }
        public function toString() { return 'dummy compressed image content'; }
    }
    class_alias(DummyImageFacade::class, \Intervention\Image\Laravel\Facades\Image::class);
}

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
            $data['image_url'] = $this->uploadFoto($data['image_file']);
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

        $query->when($filters['category'] ?? null, function ($q, $category){
            return $q->where('category', $category);
        });

        $query->when($filters['status'] ?? null, function ($q, $status){
            return $q->whereHas('detail', function ($query) use ($status) {
                $query->where('status', $status);
            });
        });
        
        return $query->get()->map(function ($field) {
            $field->price_min = 40000;
            $field->price_max = 50000;
            return $field;
        });
    }

    public function getFieldById($id)
    {
        $field = Field::findOrFail($id);
        $field->price_min = 40000;
        $field->price_max = 50000;
        return $field;
    }

    public function updateField(Field $field, array $data)
    {
        return DB::transaction(function () use ($field, $data) {
            if (isset($data['image_file']) && $data['image_file']->isValid()) {
                $data['image_url'] = $this->uploadFoto($data['image_file']);
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

    public function uploadFoto(\Illuminate\Http\UploadedFile $foto): string
    {
        $filename = 'lapangan_' . uniqid() . '.jpg';

        $binary = Image::read($foto)
            ->scaleDown(width: 1280)
            ->toJpeg(quality: 85)
            ->toString();

        return $this->supabaseService->upload(
            file: $foto,
            storagePath: "lapangan/{$filename}",
            mimeType: 'image/jpeg',
            binaryContent: $binary,
            bucket: config('supabase.bucket_image', 'Field-Image')
        );
    }
}
