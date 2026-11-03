<?php

declare(strict_types=1);

namespace App;

use App\Http\JsonResponse;
use App\Http\Response;

final class Router
{
    private array $getRoutes = [];

    public function get(string $path, callable $handler): void
    {
        $pattern = preg_replace('/\{\w+\}/', '([^/]+)', $path);
        $this->getRoutes['#^' . $pattern . '$#'] = $handler;
    }

    public function dispatch(string $method, string $path): Response
    {
        if ($method !== 'GET') {
            return new JsonResponse(['error' => 'Metoda není povolena'], 405);
        }

        foreach ($this->getRoutes as $pattern => $handler) {
            if (preg_match($pattern, $path, $matches)) {
                return $handler(...array_slice($matches, 1));
            }
        }

        return new JsonResponse(['error' => 'Stránka nebyla nalezena'], 404);
    }
}