<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cadastre\LandClassification;
use App\Http\JsonResponse;
use App\Repository\ParcelRepository;

final class ParcelController
{
    private const RUIAN_PARCEL_URL = 'https://vdp.cuzk.gov.cz/vdp/ruian/parcely/';

    public function __construct(private readonly ParcelRepository $parcels)
    {
    }

    public function show(string $id): JsonResponse
    {
        $parcelId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($parcelId === false) {
            return new JsonResponse(['error' => 'Neplatné ID parcely'], 400);
        }

        $parcel = $this->parcels->findById($parcelId);

        if ($parcel === null) {
            return new JsonResponse(['error' => 'Parcela nebyla nalezena'], 404);
        }

        return new JsonResponse([
            'id' => $parcel['id'],
            'number' => $parcel['number'],
            'cadastralArea' => [
                'code' => $parcel['cadastral_area_code'],
                'name' => $parcel['cadastral_area_name'],
            ],
            'areaM2' => $parcel['area_m2'],
            'landType' => LandClassification::landType($parcel['land_type_code']),
            'landUse' => LandClassification::landUse($parcel['land_use_code']),
            'ruianUrl' => self::RUIAN_PARCEL_URL . $parcel['id'],
        ]);
    }
}