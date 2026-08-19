<?php

namespace App\Services\Uploads;

use Illuminate\Http\UploadedFile;

interface Uploader
{
    /**
     * Store an image and return a publicly reachable URL.
     */
    public function uploadImage(UploadedFile $file, string $folder): UploadedAsset;

    /**
     * Store a document (PDF/DOC/DOCX) and return a publicly reachable URL.
     */
    public function uploadDocument(UploadedFile $file, string $folder): UploadedAsset;

    /**
     * Remove a previously stored asset. Failures are swallowed — a stale file
     * must never block the user from saving a new one.
     */
    public function delete(?string $publicId, ?string $resourceType): void;
}
