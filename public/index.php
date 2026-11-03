<?php

declare(strict_types=1);

use App\Controller\HealthController;
use App\Database;
use App\Http\JsonResponse;
use App\Router;

require __DIR__ . '/../src/bootstrap.php';

$router = new Router();

$router->get('/api/health', static fn () => (new HealthController(Database::connect()))->show());

try {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $response = $router->dispatch($_SERVER['REQUEST_METHOD'], $path);
} catch (Throwable $exception) {
    error_log((string) $exception);
    $response = new JsonResponse(['error' => 'Internal server error'], 500);
}

$response->send();