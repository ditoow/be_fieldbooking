<?php

namespace App\Services;

use App\Models\Field;
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Laravel\Facades\Image;

class FieldService
{
    protected SupabaseService $supabaseService;

    public function __construct(SupabaseService $supabaseService)
    {
        $this->supabaseService = $supabaseService;
    }

    public function createField(array $data)
    {
        if (isset($data['image_file']) && $data['image_file']->isValid()) {
            $data['image_url'] = $this->uploadFoto($data['image_file'])['url'];
        }

        return DB::transaction(function () use ($data) {
            $field = Field::create([
                'name' => $data['name'],
                'image_url' => $data['image_url'] ?? null,
                'category' => $data['category'],
                'carousel_urls' => $data['carousel_urls'] ?? null,
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
        $query = Field::with('detail');

        $query->when($filters['category'] ?? null, function ($q, $category){
            return $q->where('category', $category);
        });

        $query->when($filters['status'] ?? null, function ($q, $status){
            return $q->whereHas('detail', function ($query) use ($status) {
                $query->where('status', $status);
            });
        });
        
        return $query->get()->map(function ($field) {
            $field->price_min = config('pricing.before_16');
            $field->price_max = config('pricing.after_16');
            return $field;
        });
    }

    public function getFieldById(int $id)
    {
        $field = Field::with('detail')->findOrFail($id);
        $field->price_min = config('pricing.before_16');
        $field->price_max = config('pricing.after_16');
        return $field;
    }

    public function updateField(Field $field, array $data)
    {
        return DB::transaction(function () use ($field, $data) {
            if (isset($data['image_file']) && $data['image_file']->isValid()) {
                $data['image_url'] = $this->uploadFoto($data['image_file'])['url'];
            }

            $fieldData = array_intersect_key($data, array_flip(['name', 'image_url', 'category', 'carousel_urls']));
            if (!empty($fieldData)) {
                $field->update($fieldData);
            }

            $detailData = array_intersect_key($data, array_flip(['description', 'surface_type', 'rating', 'status']));
            if (!empty($detailData)) {
                $field->detail()->update($detailData);
            }

            return $field->fresh('detail');
        });
    }

    public function deleteField(Field $field)
    {
        Field::destroy($field->id);
    }

    public function uploadFoto(\Illuminate\Http\UploadedFile $foto): array
    {
        $filename = 'lapangan_' . uniqid() . '.webp';
        $storagePath = "lapangan/{$filename}";

        $binary = Image::read($foto)
            ->scaleDown(width: 1280)
            ->toWebp(quality: 80)
            ->toString();

        $url = $this->supabaseService->upload(
            file: $foto,
            storagePath: $storagePath,
            mimeType: 'image/webp',
            binaryContent: $binary,
            bucket: config('supabase.bucket_image', 'Field-Image')
        );

        Media::create([
            'model_type' => Field::class,
            'collection_name' => 'field_image',
            'original_name' => $foto->getClientOriginalName(),
            'stored_path' => $storagePath,
            'mime_type' => 'image/webp',
            'file_size' => strlen($binary),
            'bucket' => config('supabase.bucket_image', 'Field-Image'),
            'url' => $url,
        ]);

        return [
            'url' => $url,
            'stored_path' => $storagePath,
        ];
    }
}
