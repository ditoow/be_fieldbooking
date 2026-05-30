<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;

class CloudinaryService
{
    protected ?Cloudinary $cloudinary = null;

    protected function getClient(): Cloudinary
    {
        if ($this->cloudinary === null) {
            $this->cloudinary = new Cloudinary(config('cloudinary.cloud_url'));
        }

        return $this->cloudinary;
    }

    public function upload(string $filePath, array $options = []): array
    {
        $defaultOptions = [
            'asset_folder' => config('cloudinary.upload_folder', 'booking-files'),
            'resource_type' => 'auto',
        ];

        $mergedOptions = array_merge($defaultOptions, $options);

        return $this->getClient()->uploadApi()->upload($filePath, $mergedOptions);
    }

    public function destroy(string $publicId): array
    {
        return $this->getClient()->uploadApi()->destroy($publicId);
    }
}
