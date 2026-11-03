<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class TileRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findTile(int $z, int $x, int $y): string
    {
        $statement = $this->db->prepare(<<<SQL
            WITH bounds AS (
                SELECT ST_TileEnvelope(:z, :x, :y) AS geom
            ),
            parcel_features AS (
                SELECT p.id, p.number, p.land_type_code, ST_AsMVTGeom(p.geom, bounds.geom) AS geom
                FROM parcels p, bounds
                WHERE p.geom && bounds.geom
            ),
            area_features AS (
                SELECT a.code, a.name, ST_AsMVTGeom(a.geom, bounds.geom) AS geom
                FROM cadastral_areas a, bounds
                WHERE a.geom && bounds.geom
            ),
            parcel_layer AS (
                SELECT ST_AsMVT(parcel_features.*, 'parcels', 4096, 'geom', 'id') AS mvt
                FROM parcel_features
                WHERE parcel_features.geom IS NOT NULL
            ),
            area_layer AS (
                SELECT ST_AsMVT(area_features.*, 'cadastral_areas', 4096, 'geom', 'code') AS mvt
                FROM area_features
                WHERE area_features.geom IS NOT NULL
            )
            SELECT COALESCE(parcel_layer.mvt, ''::bytea) || COALESCE(area_layer.mvt, ''::bytea)
            FROM parcel_layer, area_layer
            SQL);
        $statement->execute(['z' => $z, 'x' => $x, 'y' => $y]);

        $tile = $statement->fetchColumn();

        return is_resource($tile) ? stream_get_contents($tile) : (string) $tile;
    }
}