<?php

namespace App\Services\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fallback used while Cloudinary credentials are not configured, so the app is
 * fully usable in local development. Note that email clients cannot load images
 * from a localhost URL — Cloudinary is required for real sending.
 */
class LocalUploader implements Uploader
{
    public function uploadImage(UploadedFile $file, string $folder): UploadedAsset
    {
        return $this->store($file, $folder, 'image');
    }

    public function uploadDocument(UploadedFile $file, string $folder): UploadedAsset
    {
        return $this->store($file, $folder, 'raw');
    }

    public function delete(?string $publicId, ?string $resourceType): void
    {
        if (filled($publicId)) {
            Storage::disk('public')->delete($publicId);
        }
    }

    private function store(UploadedFile $file, string $folder, string $resourceType): UploadedAsset
    {
        $path = $file->storeAs(
            'uploads/'.trim($folder, '/'),
            Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
            'public'
        );

        if ($path === false) {
            throw new RuntimeException('Could not write the uploaded file to local storage.');
        }

        return new UploadedAsset(
            url: Storage::disk('public')->url($path),
            publicId: $path,
            resourceType: $resourceType,
            bytes: $file->getSize() ?: 0,
            filename: $file->getClientOriginalName(),
        );
    }
}
