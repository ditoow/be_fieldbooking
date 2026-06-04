<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseService
{
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = rtrim(config('supabase.url'), '/');
        $this->key = config('supabase.key');
    }

    /**
     * Upload dari file path atau binary string ke bucket Supabase tertentu
     *
     * @param  string|\Illuminate\Http\UploadedFile  $file  path lokal atau UploadedFile
     * @param  string  $storagePath  path tujuan di bucket, contoh: "lapangan/foto_xxx.jpg"
     * @param  string  $mimeType     contoh: "image/jpeg" atau "application/pdf"
     * @param  string|null  $binaryContent  isi binary jika sudah diproses Intervention
     * @param  string|null  $bucket  Nama bucket Supabase (opsional)
     * @return string  public URL file
     */
    public function upload($file, string $storagePath, string $mimeType, ?string $binaryContent = null, ?string $bucket = null): string
    {
        $content = $binaryContent ?? file_get_contents(
            $file instanceof \Illuminate\Http\UploadedFile
                ? $file->getRealPath()
                : $file
        );

        $bucketName = $bucket ?? config('supabase.bucket_image', 'Field-Image');
        $endpoint = "{$this->url}/storage/v1/object/{$bucketName}/{$storagePath}";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->key}",
            'apikey'        => $this->key,
            'Content-Type'  => $mimeType,
        ])->withBody($content, $mimeType)->post($endpoint);

        if ($response->failed()) {
            throw new \Exception('Gagal mengunggah file ke Supabase: ' . $response->body());
        }

        return "{$this->url}/storage/v1/object/public/{$bucketName}/{$storagePath}";
    }
}
