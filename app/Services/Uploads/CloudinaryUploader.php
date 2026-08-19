<?php

namespace App\Services\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Signed server-side uploads to Cloudinary. The API secret never leaves the
 * server — the browser posts the file to Laravel, which forwards it.
 */
class CloudinaryUploader implements Uploader
{
    public function __construct(
        private string $cloudName,
        private string $apiKey,
        private string $apiSecret,
        private string $baseFolder,
    ) {}

    public static function isConfigured(): bool
    {
        return filled(config('services.cloudinary.cloud_name'))
            && filled(config('services.cloudinary.api_key'))
            && filled(config('services.cloudinary.api_secret'));
    }

    public function uploadImage(UploadedFile $file, string $folder): UploadedAsset
    {
        return $this->upload($file, $folder, 'image');
    }

    public function uploadDocument(UploadedFile $file, string $folder): UploadedAsset
    {
        // `auto` lets Cloudinary route PDFs to the image pipeline (so they get
        // thumbnails) and .doc/.docx to raw storage.
        return $this->upload($file, $folder, 'auto');
    }

    public function delete(?string $publicId, ?string $resourceType): void
    {
        if (blank($publicId)) {
            return;
        }

        $params = [
            'public_id' => $publicId,
            'timestamp' => now()->timestamp,
        ];

        try {
            Http::asForm()
                ->timeout(20)
                ->post($this->endpoint($resourceType ?: 'image', 'destroy'), [
                    ...$params,
                    'api_key' => $this->apiKey,
                    'signature' => $this->sign($params),
                ])
                ->throw();
        } catch (\Throwable $e) {
            Log::warning('Cloudinary delete failed.', ['public_id' => $publicId, 'error' => $e->getMessage()]);
        }
    }

    private function upload(UploadedFile $file, string $folder, string $resourceType): UploadedAsset
    {
        $params = [
            'folder' => trim($this->baseFolder, '/').'/'.trim($folder, '/'),
            'public_id' => Str::uuid()->toString(),
            'timestamp' => (string) now()->timestamp,
        ];

        $response = Http::timeout(60)
            ->attach('file', $file->getContent(), $file->getClientOriginalName())
            ->post($this->endpoint($resourceType, 'upload'), [
                ...$params,
                'api_key' => $this->apiKey,
                'signature' => $this->sign($params),
            ]);

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->body();

            throw new RuntimeException('Cloudinary upload failed: '.$message);
        }

        return new UploadedAsset(
            url: (string) $response->json('secure_url'),
            publicId: (string) $response->json('public_id'),
            resourceType: (string) $response->json('resource_type'),
            bytes: (int) $response->json('bytes'),
            filename: $file->getClientOriginalName(),
        );
    }

    private function endpoint(string $resourceType, string $action): string
    {
        return "https://api.cloudinary.com/v1_1/{$this->cloudName}/{$resourceType}/{$action}";
    }

    /**
     * Cloudinary signs the alphabetically sorted params, excluding file,
     * api_key and resource_type, with the API secret appended.
     *
     * @param  array<string, mixed>  $params
     */
    private function sign(array $params): string
    {
        ksort($params);

        $query = urldecode(http_build_query($params));

        return hash('sha256', $query.$this->apiSecret);
    }
}
