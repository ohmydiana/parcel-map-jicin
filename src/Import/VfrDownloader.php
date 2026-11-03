<?php

declare(strict_types=1);

namespace App\Import;

use DateTimeImmutable;
use RuntimeException;

final class VfrDownloader
{
    private const BASE_URL = 'https://vdp.cuzk.gov.cz/vymenny_format/soucasna/';

    private const MONTHS_TO_TRY = 2;

    public function __construct(private readonly string $targetDirectory)
    {
    }

    public function download(int $municipalityCode): string
    {
        if (!is_dir($this->targetDirectory) && !mkdir($this->targetDirectory, recursive: true)) {
            throw new RuntimeException("Cannot create directory {$this->targetDirectory}");
        }

        for ($monthsBack = 0; $monthsBack < self::MONTHS_TO_TRY; $monthsBack++) {
            $date = (new DateTimeImmutable('first day of this month'))
                ->modify(sprintf('-%d months', $monthsBack))
                ->modify('-1 day')
                ->format('Ymd');
            $fileName = sprintf('%s_OB_%d_UKSH.xml.zip', $date, $municipalityCode);
            $path = $this->targetDirectory . '/' . $fileName;

            if (is_file($path) || $this->fetch(self::BASE_URL . $fileName, $path)) {
                return $path;
            }
        }

        throw new RuntimeException("No VFR file found for municipality {$municipalityCode}");
    }

    private function fetch(string $url, string $path): bool
    {
        $source = @fopen($url, 'rb');

        if ($source === false) {
            return false;
        }

        $temporaryPath = $path . '.part';
        $target = fopen($temporaryPath, 'wb');
        stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);

        return rename($temporaryPath, $path);
    }
}