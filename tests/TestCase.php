<?php

namespace Tests;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sécurité : les tests ne tournent que sur la base SQLite en mémoire.
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->fail('Les tests doivent utiliser la base SQLite en mémoire (voir phpunit.xml).');
        }

        $this->withoutVite();
    }

    /** Crée un vendeur propriétaire d'une boutique. */
    protected function vendorWithStore(array $storeState = []): array
    {
        $vendor = User::factory()->create();
        $store = Store::factory()->create(array_merge(['owner_id' => $vendor->id], $storeState));

        return [$vendor, $store];
    }

    /**
     * Image PNG réelle générée sans l'extension GD (absente sous XAMPP par défaut).
     */
    protected function fakePng(string $name = 'photo.png', int $width = 120, int $height = 120): UploadedFile
    {
        $row = "\0".str_repeat("\xdb\x27\x77", $width);
        $raw = str_repeat($row, $height);
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $png = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', gzcompress($raw))
            .$chunk('IEND', '');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
