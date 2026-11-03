# Mapa parciel Jičín

Webová aplikácia, ktorá zobrazuje parcely obce Jičín na mape. Po kliknutí na parcelu ukáže jej číslo, katastrálne územie, výmeru, druh pozemku a spôsob využitia.

Pokrýva celú obec Jičín, teda 5 katastrálnych území a 16 972 parciel.

## Spustenie

**Predpoklady:** Docker s Docker Compose, internetové pripojenie a voľný port 8080. PHP ani databázu netreba inštalovať, bežia v kontajneroch.

```bash
git clone <URL repozitára>
cd parcel-map-jicin
docker compose up -d --build
docker compose exec app php scripts/import.php
```

Potom otvor v prehliadači adresu <http://localhost:8080>.

Aplikácia sa zastaví príkazom `docker compose down`. Príkaz `docker compose down -v` vymaže aj databázu.

**Použité verzie:** PHP 8.5, PostgreSQL 17, PostGIS 3.7, MapLibre GL JS 5.24.

## Architektúra

```text
ČÚZK (súbor VFR) → PHP import → PostgreSQL + PostGIS → PHP API → MapLibre + OpenStreetMap
```

| Priečinok | Obsah |
|---|---|
| `scripts/import.php` | Spustenie importu |
| `src/Import/` | Stiahnutie, čítanie VFR a zápis do databázy |
| `src/Controller/` | Spracovanie požiadaviek a kontrola vstupov |
| `src/Repository/` | SQL dotazy |
| `src/Cache/` | Súborová cache dlaždíc |
| `src/Http/` | Odpovede API |
| `src/Cadastre/` | Názvy druhov pozemkov a spôsobov využitia |
| `public/` | Vstupný bod PHP a frontend |
| `db/init/` | Schéma databázy |

| Endpoint | Odpoveď |
|---|---|
| `GET /api/tiles/{z}/{x}/{y}` | Vektorová dlaždica s parcelami a hranicami katastrálnych území |
| `GET /api/parcels/{id}` | Detail parcely v JSON |
| `GET /api/health` | Kontrola PHP, databázy a PostGIS |

## Rozhodnutia

**Dáta vopred stiahnuté, nie live.** Zadanie kladie najväčší dôraz na plynulosť, takže aplikácia nemá pri každom pohybe mapy závisieť od cudzieho servera. WFS má podľa dokumentácie ČÚZK limity na počet prvkov a veľkosť územia a sama som ho neskúšala, WMS vracia iba obrázok. Dáta sa menia pomaly, preto vlastná databáza stačí.

**RÚIAN VFR namiesto INSPIRE.** Oba súbory som stiahla a porovnala. INSPIRE obsahuje číslo, výmeru a hranicu, ale nie druh pozemku. VFR má aj druh pozemku a spôsob využitia.

**Celá obec Jičín namiesto Jičína a troch ďalších území.** Jeden súbor VFR obsahuje všetkých 5 území obce, takže je to rozumne ohraničený celok s jedným stiahnutím.

**Vektorové dlaždice a MapLibre.** Prehliadač si sťahuje iba viditeľnú časť mapy a kreslí ju cez WebGL. Dlaždice vytvára PostGIS funkciou `ST_AsMVT`, takže PHP ostáva jednoduché. MapLibre je vo verzii 5, lebo verzia 6 sa distribuuje už len ako ES moduly a projekt nemá build krok.

**Čisté PHP bez frameworku.** Malý router, controllery a repository stačia na prehľadnú štruktúru.

**Import vo vlastnom PHP.** `XMLReader` číta 38 MB súbor po jednotlivých prvkoch, takže nie je celý v pamäti.

## Čo ma prekvapilo

- **Vlastníci:** parcely vo VFR neobsahujú list vlastníctva ani vlastníkov. Detail preto odkazuje na stránku parcely v RÚIAN, odkiaľ vedie odkaz do Nahlížení do KN.
- **Kružnice:** hranice parciel obsahujú aj oblúky a kružnice. PostGIS nevie prečítať `gml:Circle`, preto ich import prevádza na oblúky.

## Čo by som s väčším časom riešila inak

- rozšírila dáta na celý okres a pre veľké oddialenie pripravila zjednodušené dlaždice vopred,
- pridala hľadanie podľa čísla parcely a filtre, napríklad podľa druhu pozemku a výmery,
- zafarbila parcely podľa druhu pozemku,
- automatizovala mesačnú aktualizáciu dát,
- pridala automatické testy importu a API,

## Zdroje dát

- [RÚIAN, výmenný formát](https://vdp.cuzk.gov.cz/vdp/ruian/vymennyformat), © ČÚZK
- [Popis formátu VFR](https://cuzk.gov.cz/vfr)
- [Podkladová mapa OpenStreetMap](https://www.openstreetmap.org/copyright), © prispievatelia OpenStreetMap