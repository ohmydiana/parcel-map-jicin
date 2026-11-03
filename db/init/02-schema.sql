CREATE TABLE cadastral_areas (
    code INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    geom geometry(MultiPolygon, 3857) NOT NULL
);

CREATE TABLE parcels (
    id BIGINT PRIMARY KEY,
    cadastral_area_code INTEGER NOT NULL REFERENCES cadastral_areas (code),
    number TEXT NOT NULL,
    area_m2 INTEGER NOT NULL,
    land_type_code SMALLINT NOT NULL,
    land_use_code SMALLINT,
    geom geometry(MultiPolygon, 3857) NOT NULL
);

CREATE INDEX cadastral_areas_geom_idx ON cadastral_areas USING GIST (geom);
CREATE INDEX parcels_geom_idx ON parcels USING GIST (geom);
CREATE INDEX parcels_cadastral_area_code_idx ON parcels (cadastral_area_code);