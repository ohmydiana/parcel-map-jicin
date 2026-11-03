<?php

declare(strict_types=1);

namespace App\Cadastre;

final class LandClassification
{
    private const LAND_TYPES = [
        2 => 'orná půda',
        5 => 'zahrada',
        6 => 'ovocný sad',
        7 => 'trvalý travní porost',
        10 => 'lesní pozemek',
        11 => 'vodní plocha',
        13 => 'zastavěná plocha a nádvoří',
        14 => 'ostatní plocha',
    ];

    private const LAND_USES = [
        2 => 'školka',
        7 => 'koryto vodního toku přirozené nebo upravené',
        8 => 'koryto vodního toku umělé',
        9 => 'vodní nádrž přírodní',
        10 => 'vodní nádrž umělá',
        11 => 'zamokřená plocha',
        12 => 'společný dvůr',
        13 => 'zbořeniště',
        14 => 'dráha',
        16 => 'silnice',
        17 => 'ostatní komunikace',
        18 => 'ostatní dopravní plocha',
        19 => 'zeleň',
        20 => 'sportoviště a rekreační plocha',
        21 => 'pohřebiště',
        23 => 'manipulační plocha',
        25 => 'skládka',
        26 => 'jiná plocha',
        27 => 'neplodná půda',
    ];

    public static function landType(int $code): string
    {
        return self::LAND_TYPES[$code] ?? "neznámý ({$code})";
    }

    public static function landUse(?int $code): ?string
    {
        if ($code === null) {
            return null;
        }

        return self::LAND_USES[$code] ?? "neznámý ({$code})";
    }
}