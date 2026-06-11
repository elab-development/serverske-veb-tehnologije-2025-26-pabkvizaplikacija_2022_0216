<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;

/**
 * Centralizovani format JSON odgovora.
 *
 * Uspešan odgovor:
 * {
 *   "uspesno": true,
 *   "podaci": { ... },
 *   "poruka": "Sezona uspešno kreirana."
 * }
 *
 * Odgovor sa greškom:
 * {
 *   "uspesno": false,
 *   "poruka": "Resurs nije pronađen.",
 *   "greske": { ... }   ← opciono, za validacione greške
 * }
 */
class ApiResponse
{
    /**
     * Uspešan odgovor sa podacima.
     */
    public static function uspesno(
        mixed $podaci = null,
        string $poruka = 'Zahtev je uspešno obrađen.',
        int $statusKod = 200
    ): JsonResponse {
        $telo = [
            'uspesno' => true,
            'poruka'  => $poruka,
        ];

        if (!is_null($podaci)) {
            $telo['podaci'] = $podaci;
        }

        return response()->json($telo, $statusKod);
    }

    /**
     * Odgovor pri kreiranju resursa (201 Created).
     */
    public static function kreirano(
        mixed $podaci = null,
        string $poruka = 'Resurs je uspešno kreiran.'
    ): JsonResponse {
        return self::uspesno($podaci, $poruka, 201);
    }

    /**
     * Odgovor pri brisanju resursa (204 No Content).
     */
    public static function obrisano(
        string $poruka = 'Resurs je uspešno obrisan.'
    ): JsonResponse {
        return response()->json([
            'uspesno' => true,
            'poruka'  => $poruka,
        ], 200);
    }

    /**
     * Odgovor sa greškom.
     */
    public static function greska(
        string $poruka = 'Došlo je do greške.',
        int $statusKod = 400,
        mixed $greske = null
    ): JsonResponse {
        $telo = [
            'uspesno' => false,
            'poruka'  => $poruka,
        ];

        if (!is_null($greske)) {
            $telo['greske'] = $greske;
        }

        return response()->json($telo, $statusKod);
    }

    /**
     * Odgovor za validacionu grešku (422).
     */
    public static function validacionaGreska(
        mixed $greske,
        string $poruka = 'Podaci nisu validni.'
    ): JsonResponse {
        return self::greska($poruka, 422, $greske);
    }

    /**
     * Odgovor kada resurs nije pronađen (404).
     */
    public static function nijePronađen(
        string $poruka = 'Resurs nije pronađen.'
    ): JsonResponse {
        return self::greska($poruka, 404);
    }

    /**
     * Odgovor za neautorizovan pristup (401).
     */
    public static function neautorizovan(
        string $poruka = 'Niste autorizovani za ovu akciju.'
    ): JsonResponse {
        return self::greska($poruka, 401);
    }

    /**
     * Odgovor za zabranjeni pristup (403).
     */
    public static function zabranjen(
        string $poruka = 'Pristup je zabranjen.'
    ): JsonResponse {
        return self::greska($poruka, 403);
    }

    /**
     * Odgovor za serversku grešku (500).
     */
    public static function serverska(
        string $poruka = 'Interna greška servera.'
    ): JsonResponse {
        return self::greska($poruka, 500);
    }
}