<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Response;
use App\Http\TileResponse;
use App\Repository\TileRepository;

final class TileController
{
    private const MAX_ZOOM = 22;

    public function __construct(private readonly TileRepository $tiles)
    {
    }

    public function show(string $z, string $x, string $y): Response
    {
        $zoom = filter_var($z, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => self::MAX_ZOOM]]);

        if ($zoom === false) {
            return new JsonResponse(['error' => 'Neplatná úroveň přiblížení'], 400);
        }

        $coordinateRange = ['options' => ['min_range' => 0, 'max_range' => 2 ** $zoom - 1]];
        $column = filter_var($x, FILTER_VALIDATE_INT, $coordinateRange);
        $row = filter_var($y, FILTER_VALIDATE_INT, $coordinateRange);

        if ($column === false || $row === false) {
            return new JsonResponse(['error' => 'Neplatné souřadnice dlaždice'], 400);
        }

        return new TileResponse($this->tiles->findTile($zoom, $column, $row));
    }
}