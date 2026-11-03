<?php

declare(strict_types=1);

namespace App\Import;

use PDO;
use Throwable;

final class ParcelImporter
{
    private const GEOMETRY_SQL = 'ST_Multi(ST_CollectionExtract(ST_MakeValid(ST_Transform(ST_CurveToLine(ST_GeomFromGML(:gml)), 3857)), 3))';

    public function __construct(private readonly PDO $db)
    {
    }

    public function import(VfrReader $reader): array
    {
        $this->db->beginTransaction();

        try {
            $this->db->exec('TRUNCATE parcels, cadastral_areas');

            $counts = [
                'areas' => $this->insertAll($reader->cadastralAreas(), <<<SQL
                    INSERT INTO cadastral_areas (code, name, geom)
                    VALUES (:code, :name, %s)
                    SQL),
                'parcels' => $this->insertAll($reader->parcels(), <<<SQL
                    INSERT INTO parcels (id, cadastral_area_code, number, area_m2, land_type_code, land_use_code, geom)
                    VALUES (:id, :cadastral_area_code, :number, :area_m2, :land_type_code, :land_use_code, %s)
                    SQL),
            ];

            $this->db->commit();

            return $counts;
        } catch (Throwable $exception) {
            $this->db->rollBack();

            throw $exception;
        }
    }

    private function insertAll(iterable $rows, string $sqlTemplate): int
    {
        $statement = $this->db->prepare(sprintf($sqlTemplate, self::GEOMETRY_SQL));
        $count = 0;

        foreach ($rows as $row) {
            $statement->execute($row);
            $count++;
        }

        return $count;
    }
}