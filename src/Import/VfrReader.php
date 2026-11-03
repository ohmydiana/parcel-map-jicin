<?php

declare(strict_types=1);

namespace App\Import;

use Generator;
use RuntimeException;
use SimpleXMLElement;
use XMLReader;

final class VfrReader
{
    private const NS_EXCHANGE_FORMAT = 'urn:cz:isvs:ruian:schemas:VymennyFormatTypy:v1';
    private const NS_PARCEL = 'urn:cz:isvs:ruian:schemas:ParcelaIntTypy:v1';
    private const NS_CADASTRAL_AREA = 'urn:cz:isvs:ruian:schemas:KatUzIntTypy:v1';
    private const NS_GML = 'http://www.opengis.net/gml/3.2';

    private const NUMBERING_BUILDING_PLOT = 1;

    public function __construct(private readonly string $zipPath)
    {
    }

    public function cadastralAreas(): Generator
    {
        foreach ($this->elements('KatastralniUzemi') as $element) {
            $area = $element->children(self::NS_CADASTRAL_AREA);

            yield [
                'code' => (int) $area->Kod,
                'name' => (string) $area->Nazev,
                'gml' => $this->gml($area->Geometrie->OriginalniHranice->children(self::NS_GML)->MultiSurface),
            ];
        }
    }

    public function parcels(): Generator
    {
        foreach ($this->elements('Parcela') as $element) {
            $parcel = $element->children(self::NS_PARCEL);
            $areaCode = $parcel->KatastralniUzemi->children(self::NS_CADASTRAL_AREA)->Kod;

            yield [
                'id' => (int) $parcel->Id,
                'cadastral_area_code' => (int) $areaCode,
                'number' => $this->parcelNumber($parcel),
                'area_m2' => (int) $parcel->VymeraParcely,
                'land_type_code' => (int) $parcel->DruhPozemkuKod,
                'land_use_code' => isset($parcel->ZpusobyVyuzitiPozemku) ? (int) $parcel->ZpusobyVyuzitiPozemku : null,
                'gml' => $this->gml($parcel->Geometrie->OriginalniHranice->children(self::NS_GML)->Polygon),
            ];
        }
    }

    private function elements(string $name): Generator
    {
        $reader = new XMLReader();
        $xmlFileName = basename($this->zipPath, '.zip');

        if (!$reader->open("zip://{$this->zipPath}#{$xmlFileName}")) {
            throw new RuntimeException("Cannot open {$this->zipPath}");
        }

        $hasNode = $reader->read();

        while ($hasNode) {
            if (
                $reader->nodeType === XMLReader::ELEMENT
                && $reader->localName === $name
                && $reader->namespaceURI === self::NS_EXCHANGE_FORMAT
                && $reader->getAttributeNs('id', self::NS_GML) !== null
            ) {
                yield new SimpleXMLElement($reader->readOuterXml());
                $hasNode = $reader->next();
            } else {
                $hasNode = $reader->read();
            }
        }

        $reader->close();
    }

    private function parcelNumber(SimpleXMLElement $parcel): string
    {
        $number = (string) $parcel->KmenoveCislo;

        if (isset($parcel->PododdeleniCisla)) {
            $number .= '/' . $parcel->PododdeleniCisla;
        }

        return (int) $parcel->DruhCislovaniKod === self::NUMBERING_BUILDING_PLOT ? "st. {$number}" : $number;
    }

    private function gml(SimpleXMLElement $geometry): string
    {
        $gml = $geometry->asXML();

        if (!str_contains($gml, 'xmlns:gml=')) {
            $gml = preg_replace('/^<gml:\w+/', '$0 xmlns:gml="' . self::NS_GML . '"', $gml, 1);
        }

        return $this->replaceCirclesWithArcs($gml);
    }

    private function replaceCirclesWithArcs(string $gml): string
    {
        return preg_replace_callback(
            '#<gml:Circle>\s*<gml:posList>([^<]+)</gml:posList>\s*</gml:Circle>#',
            function (array $matches): string {
                $positions = sprintf('<gml:posList>%s</gml:posList>', $this->circleAsArcPositions($matches[1]));

                return "<gml:ArcString>{$positions}</gml:ArcString>";
            },
            $gml,
        );
    }

    private function circleAsArcPositions(string $posList): string
    {
        [$ax, $ay, $bx, $by, $cx, $cy] = array_map('floatval', preg_split('/\s+/', trim($posList)));

        $d = 2 * ($ax * ($by - $cy) + $bx * ($cy - $ay) + $cx * ($ay - $by));
        $centerX = (($ax ** 2 + $ay ** 2) * ($by - $cy) + ($bx ** 2 + $by ** 2) * ($cy - $ay) + ($cx ** 2 + $cy ** 2) * ($ay - $by)) / $d;
        $centerY = (($ax ** 2 + $ay ** 2) * ($cx - $bx) + ($bx ** 2 + $by ** 2) * ($ax - $cx) + ($cx ** 2 + $cy ** 2) * ($bx - $ax)) / $d;

        $vx = $ax - $centerX;
        $vy = $ay - $centerY;

        return implode(' ', [
            $ax, $ay,
            $centerX - $vy, $centerY + $vx,
            $centerX - $vx, $centerY - $vy,
            $centerX + $vy, $centerY - $vx,
            $ax, $ay,
        ]);
    }
}