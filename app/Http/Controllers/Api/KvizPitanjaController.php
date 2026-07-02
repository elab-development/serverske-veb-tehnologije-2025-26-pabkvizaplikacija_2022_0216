<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KvizPitanjaController extends Controller
{
    /**
     * Bazni URL javnog OpenTriviaDB servisa.
     * Dokumentacija: https://opentdb.com/api_config.php
     */
    private string $apiUrl = 'https://opentdb.com/api.php';

    /**
     * GET /api/v1/kviz-pitanja
     * Povlači pitanja sa OpenTriviaDB javnog API-ja.
     *
     * Query parametri:
     * - kolicina   (int)    – broj pitanja, default 10, max 50
     * - kategorija (int)    – ID kategorije (npr. 18 = Computers, 23 = History)
     * - tezina     (string) – easy | medium | hard
     * - tip        (string) – multiple (višestruki izbor) | boolean (tačno/netačno)
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'kolicina'   => 'integer|min:1|max:50',
            'kategorija' => 'integer',
            'tezina'     => 'in:easy,medium,hard',
            'tip'        => 'in:multiple,boolean',
        ]);

        // Mapiranje parametara na OpenTriviaDB format
        $parametri = [
            'amount' => $request->get('kolicina', 10),
        ];

        if ($request->filled('kategorija')) {
            $parametri['category'] = $request->kategorija;
        }
        if ($request->filled('tezina')) {
            $parametri['difficulty'] = $request->tezina;
        }
        if ($request->filled('tip')) {
            $parametri['type'] = $request->tip;
        }

        // Poziv javnog API-ja
        $odgovor = Http::timeout(10)->get($this->apiUrl, $parametri);

        if (!$odgovor->successful()) {
            return ApiResponse::greska('Nije moguće povezati se sa OpenTriviaDB servisom.', 503);
        }

        $podaci = $odgovor->json();

        // OpenTriviaDB response_code:
        // 0 = Uspeh, 1 = Nema dovoljno pitanja, 2 = Nevažeći parametar
        if ($podaci['response_code'] !== 0) {
            $poruke = [
                1 => 'Nema dovoljno pitanja za zadate kriterijume.',
                2 => 'Nevažeći parametri zahteva.',
            ];

            return ApiResponse::greska(
                $poruke[$podaci['response_code']] ?? 'Greška pri povlačenju pitanja.',
                422
            );
        }

        // Formatiranje pitanja na srpski
        $pitanja = collect($podaci['results'])->map(fn($p) => [
            'pitanje'           => html_entity_decode($p['question']),
            'tacan_odgovor'     => html_entity_decode($p['correct_answer']),
            'netacni_odgovori'  => collect($p['incorrect_answers'])
                                    ->map(fn($o) => html_entity_decode($o))
                                    ->values(),
            'svi_odgovori'      => collect(array_merge(
                                    $p['incorrect_answers'],
                                    [$p['correct_answer']]
                                ))->map(fn($o) => html_entity_decode($o))
                                  ->shuffle()
                                  ->values(),
            'kategorija'        => $p['category'],
            'tezina'            => $p['difficulty'],
            'tip'               => $p['type'],
        ]);

        return ApiResponse::uspesno([
            'ukupno'  => $pitanja->count(),
            'pitanja' => $pitanja,
        ], 'Pitanja uspešno preuzeta sa OpenTriviaDB.');
    }

    /**
     * GET /api/v1/kviz-pitanja/kategorije
     * Povlači dostupne kategorije pitanja sa OpenTriviaDB.
     */
    public function kategorije(): JsonResponse
    {
        $odgovor = Http::timeout(10)->get('https://opentdb.com/api_category.php');

        if (!$odgovor->successful()) {
            return ApiResponse::greska('Nije moguće povući kategorije sa OpenTriviaDB.', 503);
        }

        $kategorije = collect($odgovor->json('trivia_categories'))
            ->map(fn($k) => [
                'id'    => $k['id'],
                'naziv' => $k['name'],
            ]);

        return ApiResponse::uspesno($kategorije, 'Lista dostupnih kategorija pitanja.');
    }
}