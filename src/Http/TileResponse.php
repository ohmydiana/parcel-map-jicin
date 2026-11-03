<?php

declare(strict_types=1);

namespace App\Http;

final class TileResponse implements Response
{
    private const HTTP_OK = 200;
    private const HTTP_NO_CONTENT = 204;
    private const BROWSER_CACHE_SECONDS = 86400;

    public function __construct(private readonly string $compressedTile)
    {
    }

    public function send(): void
    {
        header('Cache-Control: public, max-age=' . self::BROWSER_CACHE_SECONDS);

        if ($this->compressedTile === '') {
            http_response_code(self::HTTP_NO_CONTENT);

            return;
        }

        http_response_code(self::HTTP_OK);
        header('Content-Type: application/vnd.mapbox-vector-tile');
        header('Content-Encoding: gzip');

        echo $this->compressedTile;
    }
}