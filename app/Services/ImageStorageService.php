<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Image storage: Cloudinary when configured (compression, CDN, resizing),
 * local public disk otherwise. The rest of the app only sees a URL.
 */
class ImageStorageService
{
    private ?string $cloudName;

    private ?string $apiKey;

    private ?string $apiSecret;

    public function __construct(?string $cloudName = null, ?string $apiKey = null, ?string $apiSecret = null)
    {
        $this->cloudName = $cloudName ?? config('services.cloudinary.cloud_name');
        $this->apiKey = $apiKey ?? config('services.cloudinary.key');
        $this->apiSecret = $apiSecret ?? config('services.cloudinary.secret');
    }

    public function usesCloudinary(): bool
    {
        return filled($this->cloudName) && filled($this->apiKey) && filled($this->apiSecret);
    }

    public static function provider(): string
    {
        return app(self::class)->usesCloudinary() ? 'cloudinary' : 'local';
    }

    /** Configures the Cloudinary PHP SDK (global \Cloudinary / \Uploader classes). */
    private function configureCloudinary(): void
    {
        \Cloudinary::config([
            'cloud' => ['cloud_name' => $this->cloudName],
            'api' => ['key' => $this->apiKey, 'secret' => $this->apiSecret],
        ]);
    }

    /**
     * @return array{url: string, public_id: ?string, provider: string}
     */
    public function store(UploadedFile $file, string $folder = 'vehicles'): array
    {
        if ($this->usesCloudinary()) {
            return $this->storeOnCloudinary($file, $folder);
        }

        return $this->storeLocally($file, $folder);
    }

    /**
     * @return array{url: string, public_id: ?string, provider: string}
     */
    private function storeOnCloudinary(UploadedFile $file, string $folder): array
    {
        $this->configureCloudinary();

        $result = \Uploader::upload(
            $file->getRealPath(),
            [
                'folder' => "autoalert/{$folder}",
                'resource_type' => 'image',
                'overwrite' => false,
                'transformation' => [
                    ['quality' => 'auto', 'fetch_format' => 'auto'],
                ],
            ]
        );

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
            'provider' => 'cloudinary',
        ];
    }

    /**
     * @return array{url: string, public_id: ?string, provider: string}
     */
    private function storeLocally(UploadedFile $file, string $folder): array
    {
        $name = Str::uuid()->toString().'.'.($file->getClientOriginalExtension() ?: 'jpg');
        $path = $file->storeAs($folder, $name, 'public');

        return [
            'url' => Storage::disk('public')->url($path),
            // Callers that must read the bytes back (the vision model) need the
            // disk path, not the public URL.
            'path' => $path,
            'public_id' => $path,
            'provider' => 'local',
        ];
    }

    public function delete(?string $publicId, string $provider = 'local'): void
    {
        if (blank($publicId)) {
            return;
        }

        try {
            if ($provider === 'cloudinary' && $this->usesCloudinary()) {
                $this->configureCloudinary();
                \Uploader::destroy($publicId);

                return;
            }

            Storage::disk('public')->delete($publicId);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
