<?php

declare(strict_types=1);

namespace App\Http;

final class TileResponse implements Response
{
    private const HTTP_OK = 200;
    private const HTTP_NO_CONTENT = 204;

    public function __construct(private readonly string $tile)
    {
    }

    public function send(): void
    {
        if ($this->tile === '') {
            http_response_code(self::HTTP_NO_CONTENT);

            return;
        }

        http_response_code(self::HTTP_OK);
        header('Content-Type: application/vnd.mapbox-vector-tile');

        echo $this->tile;
    }
}