<?php

namespace App\Services;

use Cloudinary\Api;
use Cloudinary\Uploader;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class CloudinaryService
{
    protected bool $configured = false;

    /**
     * Cloudinary est-il configuré (CLOUDINARY_URL présent) ?
     */
    public function isConfigured(): bool
    {
        return filled(config('cloudinary.cloud_url'));
    }

    /**
     * Téléverse un fichier image et retourne ['url' => ..., 'public_id' => ...].
     *
     * @return array{url: string, public_id: string}
     */
    public function upload(UploadedFile $file, ?string $folder = null): array
    {
        $this->sdk();

        $result = Uploader::upload($file->getRealPath(), [
            'folder' => $folder ?: config('cloudinary.folder'),
            'resource_type' => 'image',
        ]);

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }

    /**
     * Supprime un fichier distant ; n'échoue jamais de façon bloquante.
     */
    public function delete(?string $publicId): void
    {
        if (! $this->isConfigured() || blank($publicId)) {
            return;
        }

        try {
            $this->sdk();
            (new Api)->delete_resources([$publicId]);
        } catch (\Throwable) {
            // La ressource distante n'existe peut-être plus ; on ignore.
        }
    }

    protected function sdk(): void
    {
        if (! $this->isConfigured()) {
            throw new InvalidArgumentException('Cloudinary n\'est pas configuré (CLOUDINARY_URL manquant dans le .env).');
        }

        if (! $this->configured) {
            \Cloudinary::config_from_url(config('cloudinary.cloud_url'));
            $this->configured = true;
        }
    }
}
