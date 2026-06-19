<?php

namespace App\Services;

use App\Models\Field;
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Laravel\Facades\Image;

class FieldService
{
    protected SupabaseService $supabaseService;
    protected ScheduleService $scheduleService;

    public function __construct(SupabaseService $supabaseService, ScheduleService $scheduleService)
    {
        $this->supabaseService = $supabaseService;
        $this->scheduleService = $scheduleService;
    }

    protected function parseSpecifications(array $data): array
    {
        if (!isset($data['specifications'])) {
            return $data;
        }

        if (is_string($data['specifications'])) {
            $decoded = json_decode($data['specifications'], true);
            $data['specifications'] = is_array($decoded) ? $decoded : [];
        }

        return $data;
    }

    public function createField(array $data)
    {
        if (isset($data['image_file']) && $data['image_file']->isValid()) {
            $data['image_url'] = $this->uploadFoto($data['image_file'])['url'];
        }

        $data = $this->parseSpecifications($data);

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
                'carousel_urls' => $data['carousel_urls'] ?? null,
            ]);

            if (!empty($data['specifications'])) {
                foreach ($data['specifications'] as $i => $spec) {
                    $field->specifications()->create([
                        'label' => $spec['label'],
                        'value' => $spec['value'] ?? '',
                        'sort_order' => $i,
                    ]);
                }
            }

            return $field;
        });
    }

    public function getAllFields($filters = [])
    {
        $query = Field::with(['detail', 'specifications']);

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
        $field = Field::with(['detail', 'schedules', 'specifications'])->findOrFail($id);
        $field->price_min = config('pricing.before_16');
        $field->price_max = config('pricing.after_16');
        $field->available_slots_today = $this->countAvailableSlotsToday($id);
        return $field;
    }

    public function getFieldByName(string $name, ?int $disambiguateId = null)
    {
        $query = Field::with(['detail', 'schedules', 'specifications'])->whereRaw('LOWER(name) = ?', [strtolower($name)]);

        if ($disambiguateId) {
            $query->where('id', $disambiguateId);
        }

        $field = $query->first();

        if (!$field) {
            return null;
        }

        $field->price_min = config('pricing.before_16');
        $field->price_max = config('pricing.after_16');
        $field->available_slots_today = $this->countAvailableSlotsToday($field->id);
        return $field;
    }

    protected function countAvailableSlotsToday(int $fieldId): int
    {
        $today = now()->toDateString();
        $now = now();
        $slots = $this->scheduleService->getAllSlots($fieldId, $today);
        return collect($slots)
            ->where('status', 'available')
            ->filter(function ($slot) use ($now) {
                return $slot['start_time'] >= $now->format('H:i');
            })
            ->count();
    }

    public function updateField(Field $field, array $data)
    {
        $data = $this->parseSpecifications($data);

        return DB::transaction(function () use ($field, $data) {
            if (isset($data['image_file']) && $data['image_file']->isValid()) {
                $data['image_url'] = $this->uploadFoto($data['image_file'])['url'];
            }

            $fieldData = array_intersect_key($data, array_flip(['name', 'image_url', 'category']));
            if (!empty($fieldData)) {
                $field->update($fieldData);
            }

            $detailData = array_intersect_key($data, array_flip(['description', 'surface_type', 'rating', 'status', 'carousel_urls']));
            if (!empty($detailData)) {
                $field->detail()->update($detailData);
            }

            if (isset($data['specifications'])) {
                $field->specifications()->delete();
                foreach ($data['specifications'] as $i => $spec) {
                    $field->specifications()->create([
                        'label' => $spec['label'],
                        'value' => $spec['value'] ?? '',
                        'sort_order' => $i,
                    ]);
                }
            }

            return $field->fresh(['detail', 'specifications']);
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
