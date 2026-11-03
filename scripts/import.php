<?php

declare(strict_types=1);

use App\Database;
use App\Import\ParcelImporter;
use App\Import\VfrDownloader;
use App\Import\VfrReader;

require __DIR__ . '/../src/bootstrap.php';

const JICIN_MUNICIPALITY_CODE = 572659;

$startedAt = microtime(true);

echo "Downloading data...\n";
$zipPath = (new VfrDownloader(__DIR__ . '/../data'))->download(JICIN_MUNICIPALITY_CODE);
echo 'Using ' . basename($zipPath) . "\n";

echo "Importing...\n";
$counts = (new ParcelImporter(Database::connect()))->import(new VfrReader($zipPath));

printf(
    "Imported %d cadastral areas and %d parcels in %.1f s\n",
    $counts['areas'],
    $counts['parcels'],
    microtime(true) - $startedAt,
);