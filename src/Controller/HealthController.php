<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use PDO;

final class HealthController
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function show(): JsonResponse
    {
        $postgisVersion = $this->db->query('SELECT PostGIS_Lib_Version()')->fetchColumn();

        return new JsonResponse([
            'status' => 'ok',
            'php' => PHP_VERSION,
            'postgis' => $postgisVersion,
        ]);
    }
}