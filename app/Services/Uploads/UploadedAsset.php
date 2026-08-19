<?php

namespace App\Services\Uploads;

class UploadedAsset
{
    public function __construct(
        public string $url,
        public string $publicId,
        public string $resourceType,
        public int $bytes,
        public string $filename,
    ) {}
}
