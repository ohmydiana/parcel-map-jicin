<?php

declare(strict_types=1);

namespace App\Cache;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class TileCache
{
    private readonly string $directory;

    public function __construct()
    {
        $this->directory = sys_get_temp_dir() . '/parcel-map-tiles';
    }

    public function get(int $z, int $x, int $y): ?string
    {
        $tile = @file_get_contents($this->path($z, $x, $y));

        return $tile === false ? null : $tile;
    }

    public function put(int $z, int $x, int $y, string $tile): void
    {
        $path = $this->path($z, $x, $y);
        $directory = dirname($path);

        if (!is_dir($directory)) {
            @mkdir($directory, recursive: true);
        }

        $temporaryPath = $path . '.' . getmypid() . '.part';

        if (@file_put_contents($temporaryPath, $tile) !== false) {
            rename($temporaryPath, $path);
        }
    }

    public function clear(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($entries as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
    }

    private function path(int $z, int $x, int $y): string
    {
        return "{$this->directory}/{$z}/{$x}/{$y}.mvt.gz";
    }
}