<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Enregistre les images téléversées (déjà validées par un Form Request)
 * sous un nom aléatoire, rangées par boutique.
 */
class ImageUploader
{
    public function store(UploadedFile $file, string $directory): string
    {
        // hashName() génère un nom aléatoire et choisit l'extension à partir du contenu réel (MIME).
        return $file->store($directory, 'public');
    }

    public function replace(?string $oldPath, UploadedFile $file, string $directory): string
    {
        $path = $this->store($file, $directory);
        $this->delete($oldPath);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
