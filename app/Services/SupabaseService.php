<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseService
{
    protected string $url;
    protected string $key;
    protected string $bucket;

    public function __construct()
    {
        $this->url = rtrim(config('supabase.url'), '/');
        $this->key = config('supabase.key');
        $this->bucket = config('supabase.bucket', 'Field-Image');
    }

    /**
     * Upload a file to Supabase storage.
     *
     * @param string $filePath Local file path.
     * @param array $options Configuration options (like asset_folder / folder).
     * @return array Returns an array resembling Cloudinary result keys for backward compatibility.
     */
    public function upload(string $filePath, array $options = []): array
    {
        $folder = $options['asset_folder'] ?? null;
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $filename = Str::random(40) . ($extension ? '.' . $extension : '');
        $path = $folder ? trim($folder, '/') . '/' . $filename : $filename;

        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        $fileContents = file_get_contents($filePath);

        $uploadUrl = "{$this->url}/storage/v1/object/{$this->bucket}/{$path}";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->key}",
            'apikey' => $this->key,
            'Content-Type' => $mimeType,
        ])->withBody($fileContents, $mimeType)->post($uploadUrl);

        if ($response->failed()) {
            throw new \Exception('Gagal mengunggah file ke Supabase: ' . $response->body());
        }

        $publicUrl = "{$this->url}/storage/v1/object/public/{$this->bucket}/{$path}";

        return [
            'secure_url' => $publicUrl,
        ];
    }
}
