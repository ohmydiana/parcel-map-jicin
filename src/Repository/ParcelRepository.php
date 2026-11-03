<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ParcelRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare(<<<SQL
            SELECT
                p.id,
                p.number,
                p.area_m2,
                p.land_type_code,
                p.land_use_code,
                a.code AS cadastral_area_code,
                a.name AS cadastral_area_name
            FROM parcels p
            JOIN cadastral_areas a ON a.code = p.cadastral_area_code
            WHERE p.id = :id
            SQL);
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }
}