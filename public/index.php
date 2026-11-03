<?php

declare(strict_types=1);

use App\Controller\HealthController;
use App\Controller\ParcelController;
use App\Controller\TileController;
use App\Database;
use App\Http\JsonResponse;
use App\Repository\ParcelRepository;
use App\Repository\TileRepository;
use App\Router;

require __DIR__ . '/../src/bootstrap.php';

$router = new Router();

$router->get('/api/health', static fn () => (new HealthController(Database::connect()))->show());

$router->get('/api/parcels/{id}', static fn (string $id) => (
    new ParcelController(new ParcelRepository(Database::connect()))
)->show($id));

$router->get('/api/tiles/{z}/{x}/{y}', static fn (string $z, string $x, string $y) => (
    new TileController(new TileRepository(Database::connect()))
)->show($z, $x, $y));

try {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $response = $router->dispatch($_SERVER['REQUEST_METHOD'], $path);
} catch (Throwable $exception) {
    error_log((string) $exception);
    $response = new JsonResponse(['error' => 'Interní chyba serveru'], 500);
}

$response->send();